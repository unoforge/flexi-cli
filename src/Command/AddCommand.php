<?php

namespace FlexiCli\Command;

use FlexiCore\Core\{Constants, RegistryComponentReference, RegistryStore, RegistryVersionResolver};
use FlexiCore\Installer\PackageInstaller;
use FlexiCore\Service\ProjectDetector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\{confirm, spin};

class AddCommand extends Command
{
    private string $defaultSource;
    private array $registries;
    private string $projectRoot;
    private array $installedRegistryComponents = [];
    private array $pendingCommands = [];
    private array $createdFiles = [];
    private array $overwrittenFiles = [];
    private array $skippedFiles = [];
    private array $postInstallMessages = [];
    private array $resolvedRegistries = [];
    private ?OutputInterface $output = null;
    private bool $skipPackageInstallation = false;
    private array $pendingDependencies = [];
    private bool $dryRun = false;
    private bool $forceRewrite = false;
    private bool $forceNoRewrite = false;

    public function __construct(
        private RegistryStore $store = new RegistryStore(),
        private RegistryVersionResolver $versionResolver = new RegistryVersionResolver()
    ) {
        parent::__construct();
        $this->projectRoot = getcwd();
        $this->loadConfiguration();
    }

    protected function configure(): void
    {
        $this
            ->setName('add')
            ->setDescription('Add UI components to your project from component registries')
            ->addArgument('components', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Component refs to add (e.g. button, button@0.0.2, @fly-ui/button@0.0.1)')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Namespace to use for all components')
            ->addOption('skip-deps', null, InputOption::VALUE_NONE, 'Skip dependency installation')
            ->addOption('rewrite', null, InputOption::VALUE_NONE, 'Rewrite existing files for already installed components')
            ->addOption('no-rewrite', null, InputOption::VALUE_NONE, 'Do not rewrite existing files for already installed components')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Show planned changes only; do not write files or install dependencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $components = $input->getArgument('components');
        $namespace = $input->getOption('namespace');
        $skipDeps = (bool) $input->getOption('skip-deps');
        $this->dryRun = (bool) $input->getOption('dry');
        $this->forceRewrite = (bool) $input->getOption('rewrite');
        $this->forceNoRewrite = (bool) $input->getOption('no-rewrite');

        if ($this->forceRewrite && $this->forceNoRewrite) {
            $output->writeln('<fg=red>Cannot use --rewrite and --no-rewrite together.</>');
            return Command::FAILURE;
        }

        if (!$this->configExists()) {
            $output->writeln('Flexiwind not initialized, Run init command first');
            return Command::FAILURE;
        }

        $this->store->init();

        foreach ($components as $componentInput) {
            $this->addComponent((string) $componentInput, is_string($namespace) ? $namespace : null, $skipDeps);
        }

        if (!$skipDeps) {
            $this->flushPendingDependencies();
        }

        if ($this->dryRun) {
            $this->renderDryRunSummary();
            return Command::SUCCESS;
        }

        if (!empty($this->createdFiles) || !empty($this->overwrittenFiles)) {
            $output->writeln('<fg=green>====== Everything installed ======</>');
            foreach ($this->createdFiles as $fileCreated) {
                $output->writeln("<fg=green>✓ Created : {$fileCreated}</>");
            }
            foreach ($this->overwrittenFiles as $fileOverwritten) {
                $output->writeln("<fg=yellow>↺ Overwritten : {$fileOverwritten}</>");
            }

            $this->renderPostInstallMessages();
            $output->writeln('<fg=green>====== Operation completed ======</>');
        }

        return Command::SUCCESS;
    }

    private function configExists(): bool
    {
        return file_exists($this->projectRoot . '/flexiwind.yaml');
    }

    private function loadConfiguration(): void
    {
        $configPath = $this->projectRoot . '/flexiwind.yaml';

        if (!file_exists($configPath)) {
            $this->registries = [];
            $this->defaultSource = Constants::LOCAL_REGISTRY;
            return;
        }

        $config = Yaml::parseFile($configPath);
        $this->defaultSource = $config['defaultSource'] ?? Constants::LOCAL_REGISTRY;
        $this->registries = $config['registries'] ?? [];
    }

    private function addComponent(string $componentInput, ?string $namespace, bool $skipDeps = false): void
    {
        try {
            $reference = RegistryComponentReference::parse($componentInput);
        } catch (\InvalidArgumentException $e) {
            $this->output?->writeln('<fg=red>' . $e->getMessage() . '</>');
            return;
        }

        $source = $this->determineSource($reference, $namespace);
        $resolved = $this->fetchRegistry($reference, $source);

        if (!$resolved) {
            $this->output?->writeln("<fg=red>⚠️ Registry not found for component: {$reference->toDisplay()}</>");
            return;
        }

        $registryJson = $resolved['registry'];
        $resolvedVersion = $resolved['resolvedVersion'] ?? ($registryJson['version'] ?? Constants::DEFAULT_COMPONENT_VERSION);
        $this->resolvedRegistries[] = [
            'component' => $reference->component,
            'requested' => $reference->version,
            'resolved' => $resolvedVersion,
            'url' => $resolved['url'] ?? '',
        ];

        if (!isset($registryJson['files']) || !is_array($registryJson['files'])) {
            $this->output?->writeln("<fg=red>⚠️ Invalid registry: no files for {$reference->component}</>");
            return;
        }

        $storeNamespace = $reference->namespace ?? $namespace ?? 'flexiwind';
        $storeName = $reference->component;
        $isInstalled = $this->store->exists($storeName, $storeNamespace);
        $installedVersion = $this->store->getVersion($storeName, $storeNamespace);

        $rewrite = $this->resolveRewriteDecision(
            $reference,
            $isInstalled,
            $installedVersion,
            is_string($resolvedVersion) ? $resolvedVersion : null
        );
        if ($isInstalled && !$rewrite) {
            $this->output?->writeln("<fg=yellow>Skipping {$reference->component}. Use --rewrite, {$reference->component}@<version>, or upgrade {$reference->component}.</>");
            return;
        }

        $this->output?->writeln("<fg=blue>Adding component: {$reference->toDisplay()}</>");

        if (isset($registryJson['registryDependencies']) && is_array($registryJson['registryDependencies'])) {
            $this->handleRegistryDependencies($registryJson['registryDependencies'], $namespace);
        }

        if (!$skipDeps) {
            $this->handlePackageDependencies($registryJson);
        }

        if ($this->dryRun) {
            foreach ($registryJson['files'] as $file) {
                $this->processFile($file, $rewrite);
            }
        } else {
            spin(message: 'Processing files...', callback: function () use ($registryJson, $rewrite): void {
                foreach ($registryJson['files'] as $file) {
                    $this->processFile($file, $rewrite);
                }
            });
        }

        $this->installedRegistryComponents[] = $reference->component;

        if (isset($registryJson['message'])) {
            $this->collectPostInstallMessage($registryJson['message']);
        }

        if (!$this->dryRun) {
            $this->store->add(
                $storeName,
                $storeNamespace,
                is_string($resolvedVersion) ? $resolvedVersion : Constants::DEFAULT_COMPONENT_VERSION,
                $registryJson['message'] ?? null
            );
            $this->output?->writeln("<fg=green>✔ {$reference->component} added successfully</>");
            return;
        }

        $this->output?->writeln("<fg=yellow>[dry] Planned install for {$reference->toDisplay()}</>");
    }

    private function resolveRewriteDecision(
        RegistryComponentReference $reference,
        bool $isInstalled,
        ?string $installedVersion,
        ?string $resolvedVersion
    ): bool
    {
        if (!$isInstalled) {
            return false;
        }

        if ($this->forceRewrite) {
            return true;
        }

        if ($this->forceNoRewrite) {
            return false;
        }

        $targetVersion = $reference->version ?? $resolvedVersion;
        if ($targetVersion !== null && $installedVersion !== null && $targetVersion !== $installedVersion) {
            if ($this->dryRun) {
                $this->output?->writeln("<fg=cyan>[dry] Would update {$reference->component} from {$installedVersion} to {$targetVersion}</>");
                return true;
            }

            return confirm(
                "Component {$reference->component} is installed at {$installedVersion}; requested {$targetVersion}. Overwrite current files and update?",
                false
            );
        }

        if ($this->dryRun) {
            return true;
        }

        $installedLabel = $installedVersion ? " @ {$installedVersion}" : '';
        return confirm("Component {$reference->component}{$installedLabel} is already installed. Rewrite existing files?", false);
    }

    private function handleRegistryDependencies(array $registryDependencies, ?string $namespace): void
    {
        $this->output?->writeln('<fg=blue>🔍 Found registry dependencies, checking...</>');

        foreach ($registryDependencies as $dependency) {
            try {
                $dependencyRef = RegistryComponentReference::parse((string) $dependency);
            } catch (\InvalidArgumentException) {
                $this->output?->writeln("<fg=red>⚠️ Invalid registry dependency reference: {$dependency}</>");
                continue;
            }

            $this->output?->writeln("<fg=blue>  → Checking: {$dependencyRef->toDisplay()}</>");

            if (\in_array($dependencyRef->component, $this->installedRegistryComponents, true)) {
                $this->output?->writeln("<fg=green>  ✔ {$dependencyRef->component} already processed in this session, skipping.</>");
                continue;
            }

            $depNamespace = $dependencyRef->namespace ?? $namespace ?? 'flexiwind';
            $alreadyInStore = $this->store->exists($dependencyRef->component, $depNamespace) && $dependencyRef->version === null;

            if ($alreadyInStore) {
                $this->output?->writeln("<fg=yellow>  ⚠ Registry dependency already present: {$dependencyRef->component}</>");

                if ($this->forceRewrite) {
                    $this->output?->writeln("<fg=yellow>  ↺ --rewrite flag set, reinstalling {$dependencyRef->component}...</>");
                } elseif ($this->forceNoRewrite || $this->dryRun) {
                    $this->output?->writeln("<fg=yellow>  Skipping {$dependencyRef->component}.</>");
                    continue;
                } else {
                    $overwrite = confirm("  Registry dependency \"{$dependencyRef->component}\" is already installed. Overwrite it?", false);
                    if (!$overwrite) {
                        $this->output?->writeln("<fg=yellow>  Skipping {$dependencyRef->component}.</>");
                        continue;
                    }
                }
            } else {
                $this->output?->writeln("<fg=yellow>  ↓ Registry dependency not found locally, installing: {$dependencyRef->toDisplay()}</>");
            }

            $this->addComponent($dependencyRef->toDisplay(), $namespace, false);
        }
    }

    private function handlePackageDependencies(array $registryJson): void
    {
        $dependencies = $registryJson['dependencies'] ?? [];
        $devDependencies = $registryJson['devDependencies'] ?? [];

        $composerDeps = array_merge($dependencies['composer'] ?? [], $devDependencies['composer'] ?? []);
        $nodeDeps = array_merge($dependencies['node'] ?? [], $devDependencies['node'] ?? []);

        if (empty($composerDeps) && empty($nodeDeps)) {
            return;
        }

        if ($this->dryRun) {
            $this->savePendingCommands($dependencies, $devDependencies);
            return;
        }

        // Accumulate deps to be installed later in a single prompt
        foreach ($dependencies['composer'] ?? [] as $dep) {
            $this->pendingDependencies['composer']['prod'][] = $dep;
        }
        foreach ($devDependencies['composer'] ?? [] as $dep) {
            $this->pendingDependencies['composer']['dev'][] = $dep;
        }
        foreach ($dependencies['node'] ?? [] as $dep) {
            $this->pendingDependencies['node']['prod'][] = $dep;
        }
        foreach ($devDependencies['node'] ?? [] as $dep) {
            $this->pendingDependencies['node']['dev'][] = $dep;
        }
    }

    private function flushPendingDependencies(): void
    {
        if (empty($this->pendingDependencies)) {
            return;
        }

        $composerDeps = array_unique($this->pendingDependencies['composer']['prod'] ?? []);
        $composerDevDeps = array_unique($this->pendingDependencies['composer']['dev'] ?? []);
        $nodeDeps = array_unique($this->pendingDependencies['node']['prod'] ?? []);
        $nodeDevDeps = array_unique($this->pendingDependencies['node']['dev'] ?? []);

        if (count($composerDeps) + count($composerDevDeps) > 0) {
            $this->output?->writeln('<fg=yellow>Composer dependencies required:</>');
            foreach (array_merge($composerDeps, $composerDevDeps) as $dep) {
                $this->output?->writeln("<fg=yellow>     → {$dep}</>");
            }
        }
        if (count($nodeDeps) + count($nodeDevDeps) > 0) {
            $this->output?->writeln('<fg=yellow>Node dependencies required:</>');
            foreach (array_merge($nodeDeps, $nodeDevDeps) as $dep) {
                $this->output?->writeln("<fg=yellow>     → {$dep}</>");
            }
        }

        if (!confirm('Install all dependencies now?', true)) {
            $this->output?->writeln('<fg=red>Skipping dependency installation. You may need to install them manually.</>');
            $this->skipPackageInstallation = true;
            $this->savePendingCommands(
                ['composer' => $composerDeps, 'node' => $nodeDeps],
                ['composer' => $composerDevDeps, 'node' => $nodeDevDeps]
            );
            $this->showPendingCommands();
            return;
        }

        if (ProjectDetector::check_Composer($this->projectRoot) && (!empty($composerDeps) || !empty($composerDevDeps))) {
            $this->installComposerDependencies(array_values($composerDeps), array_values($composerDevDeps));
        }

        $packageManager = ProjectDetector::getNodePackageManager();
        if ($packageManager && file_exists($this->projectRoot . '/package.json') && (!empty($nodeDeps) || !empty($nodeDevDeps))) {
            $this->installNodeDependencies(array_values($nodeDeps), array_values($nodeDevDeps), $packageManager);
        }
    }

    private function installComposerDependencies(array $dependencies, array $devDependencies): void
    {
        if (empty($dependencies) && empty($devDependencies)) {
            return;
        }

        $composer = PackageInstaller::composer($this->projectRoot);
        $this->output?->writeln('=================Installing Composer dependencies...=================');
        if (!empty($dependencies)) {
            foreach ($dependencies as $dep) {
                $packageName = $this->extractPackageName($dep);
                if (!$composer->isInstalled($packageName)) {
                    spin(
                        message: "Installing {$dep}...",
                        callback: fn() => $composer->install($dep, false)
                    );
                    $this->output?->writeln("<fg=green>✓ Installed {$dep}</>");
                }
            }
        }
        if (!empty($devDependencies)) {
            foreach ($devDependencies as $dep) {
                $packageName = $this->extractPackageName($dep);
                if (!$composer->isInstalled($packageName)) {
                    spin(
                        message: "Installing {$dep} (dev)...",
                        callback: fn() => $composer->install($dep, true)
                    );
                    $this->output?->writeln("<fg=green>✓ Installed {$dep} (dev)</>");
                }
            }
        }
        $this->output?->writeln('=================Composer dependencies installed=================');
    }

    private function installNodeDependencies(array $dependencies, array $devDependencies, string $packageManager): void
    {
        if (empty($dependencies) && empty($devDependencies)) {
            return;
        }

        $node = PackageInstaller::node($packageManager, $this->projectRoot);

        $this->output?->writeln('=================Installing Node dependencies...=================');
        if (!empty($dependencies)) {
            foreach ($dependencies as $dep) {
                $packageName = $this->extractPackageName($dep);
                if (!$node->isInstalled($packageName)) {
                    spin(
                        message: "Installing {$dep}...",
                        callback: fn() => $node->install($dep, false)
                    );
                    $this->output?->writeln("<fg=green>✓ Installed {$dep}</>");
                }
            }
        }

        if (!empty($devDependencies)) {
            $this->output?->writeln('<fg=yellow>Installing Node.js dev dependencies...</>');
            foreach ($devDependencies as $dep) {
                $packageName = $this->extractPackageName($dep);
                if (!$node->isInstalled($packageName)) {
                    spin(
                        message: "Installing {$dep} (dev)...",
                        callback: fn() => $node->install($dep, true)
                    );
                }
            }
        }
    }

    private function extractPackageName(string $dependency): string
    {
        return explode('@', $dependency)[0];
    }

    private function determineSource(RegistryComponentReference $reference, ?string $namespace): array
    {
        if ($namespace) {
            if (!isset($this->registries[$namespace])) {
                throw new \RuntimeException("Namespace {$namespace} not found in configuration.");
            }
            return $this->parseRegistryConfig($this->registries[$namespace]);
        }

        if ($reference->namespace !== null) {
            if (!isset($this->registries[$reference->namespace])) {
                throw new \RuntimeException("Namespace {$reference->namespace} not found in configuration.");
            }
            return $this->parseRegistryConfig($this->registries[$reference->namespace]);
        }

        return ['baseUrl' => $this->defaultSource];
    }

    private function parseRegistryConfig(mixed $config): array
    {
        if (is_string($config)) {
            return ['baseUrl' => $config];
        }

        if (is_array($config)) {
            $result = ['baseUrl' => $config['url'] ?? $config['baseUrl'] ?? ''];

            if (isset($config['headers'])) {
                $result['headers'] = $this->expandEnvironmentVariables($config['headers']);
            }

            if (isset($config['params'])) {
                $result['params'] = $config['params'];
            }

            return $result;
        }

        throw new \RuntimeException('Invalid registry configuration format');
    }

    private function expandEnvironmentVariables(array $headers): array
    {
        $expanded = [];
        foreach ($headers as $key => $value) {
            $expanded[$key] = preg_replace_callback('/\$\{([^}]+)\}/', function ($matches) {
                return $_ENV[$matches[1]] ?? $matches[0];
            }, (string) $value);
        }
        return $expanded;
    }

    /**
     * @return array{registry: array, resolvedVersion: string|null, url: string}|null
     */
    private function fetchRegistry(RegistryComponentReference $reference, array $source): ?array
    {
        return $this->versionResolver->resolve(
            $source['baseUrl'],
            $reference->componentName,
            $reference->version,
            $source['headers'] ?? [],
            $source['params'] ?? []
        );
    }

    private function processFile(array $file, bool $rewrite): void
    {
        $target = (string) ($file['target'] ?? '');
        if ($target === '') {
            return;
        }

        $targetPath = $this->projectRoot . '/' . $target;
        $exists = file_exists($targetPath);

        if ($exists && !$rewrite) {
            $this->skippedFiles[] = $target;
            $this->output?->writeln("<fg=red>⚠️ File exists, skipping: {$target}</>");
            return;
        }

        if ($this->dryRun) {
            if ($exists) {
                $this->overwrittenFiles[] = $target;
                $this->output?->writeln("<fg=yellow>[dry] overwrite: {$target}</>");
            } else {
                $this->createdFiles[] = $target;
                $this->output?->writeln("<fg=green>[dry] create: {$target}</>");
            }
            return;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($targetPath, (string) ($file['content'] ?? ''));

        if ($exists) {
            $this->overwrittenFiles[] = $target;
            return;
        }

        $this->createdFiles[] = $target;
    }

    private function savePendingCommands(array $dependencies, array $devDependencies): void
    {
        $composerDeps = $dependencies['composer'] ?? [];
        $composerDevDeps = $devDependencies['composer'] ?? [];
        $nodeDeps = $dependencies['node'] ?? [];
        $nodeDevDeps = $devDependencies['node'] ?? [];

        if (!empty($composerDeps) && ProjectDetector::check_Composer($this->projectRoot)) {
            $this->pendingCommands[] = 'composer require ' . implode(' ', array_map('escapeshellarg', $composerDeps));
        }

        if (!empty($composerDevDeps) && ProjectDetector::check_Composer($this->projectRoot)) {
            $this->pendingCommands[] = 'composer require --dev ' . implode(' ', array_map('escapeshellarg', $composerDevDeps));
        }

        $packageManager = ProjectDetector::getNodePackageManager();
        if ($packageManager && file_exists($this->projectRoot . '/package.json')) {
            if (!empty($nodeDeps)) {
                $this->pendingCommands[] = $this->buildNodeInstallCommand($nodeDeps, false, $packageManager);
            }

            if (!empty($nodeDevDeps)) {
                $this->pendingCommands[] = $this->buildNodeInstallCommand($nodeDevDeps, true, $packageManager);
            }
        }

        $this->pendingCommands = array_values(array_unique($this->pendingCommands));
    }

    private function buildNodeInstallCommand(array $packages, bool $isDevDep, string $packageManager): string
    {
        $escapedPackages = array_map('escapeshellarg', $packages);
        $packagesString = implode(' ', $escapedPackages);
        return PackageInstaller::node($packageManager)->buildInstallCommand($packagesString, $isDevDep);
    }

    private function showPendingCommands(): void
    {
        if (count($this->pendingCommands) > 0) {
            $this->output?->writeln('📋 Manual installation required');
            $this->output?->writeln('Run the following commands manually to install dependencies:');
            foreach ($this->pendingCommands as $command) {
                $this->output?->writeln("  {$command}");
            }
        }
    }

    private function collectPostInstallMessage(mixed $message): void
    {
        if (is_string($message)) {
            $trimmed = trim($message);
            if ($trimmed !== '') {
                $this->postInstallMessages[] = $trimmed;
            }
            return;
        }

        if (is_array($message)) {
            foreach ($message as $entry) {
                $this->collectPostInstallMessage($entry);
            }
        }
    }

    private function renderPostInstallMessages(): void
    {
        $messages = array_values(array_unique($this->postInstallMessages));

        if (empty($messages)) {
            return;
        }

        $this->output?->writeln('');
        $this->output?->writeln('<fg=yellow>Good to know: components.json contains guides to help you consuming the installed registries.</>');
    }

    private function renderDryRunSummary(): void
    {
        $this->output?->writeln('');
        $this->output?->writeln('<fg=cyan>====== Dry Run Summary ======</>');

        if (!empty($this->resolvedRegistries)) {
            $this->output?->writeln('<fg=cyan>Registry resolution:</>');
            foreach ($this->resolvedRegistries as $entry) {
                $requested = $entry['requested'] ? (' requested=' . $entry['requested']) : ' requested=latest';
                $resolved = $entry['resolved'] ? (' resolved=' . $entry['resolved']) : '';
                $url = $entry['url'] ? (' url=' . $entry['url']) : '';
                $this->output?->writeln("  - {$entry['component']}{$requested}{$resolved}{$url}");
            }
        }

        $this->output?->writeln('<fg=cyan>Files:</>');
        $this->output?->writeln('  create: ' . count($this->createdFiles));
        $this->output?->writeln('  overwrite: ' . count($this->overwrittenFiles));
        $this->output?->writeln('  skip: ' . count($this->skippedFiles));

        if (!empty($this->pendingCommands)) {
            $this->output?->writeln('<fg=cyan>Dependency commands (planned only):</>');
            foreach ($this->pendingCommands as $command) {
                $this->output?->writeln('  ' . $command);
            }
        }

        $this->output?->writeln('<fg=cyan>================================</>');
    }
}
