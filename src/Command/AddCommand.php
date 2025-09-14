<?php

namespace FlexiCli\Command;

use FlexiCli\Core\{Constants, RegistryStore};
use FlexiCli\Utils\HttpUtils;
use FlexiCli\Service\ProjectDetector;
use FlexiCli\Installer\PackageInstaller;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\{spin, confirm};

class AddCommand extends Command
{
    private string $defaultSource;
    private array $registries;
    private string $projectRoot;
    private array $installedRegistryComponents = [];
    private array $pendingCommands = [];
    private array $createdFiles = [];
    private ?OutputInterface $output = null;
    private bool $skipPackageInstallation = false;

    public function __construct(
        private RegistryStore $store = new RegistryStore()
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
            ->addArgument('components', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Component names to add')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Namespace to use for all components')
            ->addOption('skip-deps', null, InputOption::VALUE_NONE, 'Skip dependency installation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $components = $input->getArgument('components');
        $namespace = $input->getOption('namespace');
        $skipDeps = $input->getOption('skip-deps');

        if (!$this->configExists()) {
            $output->writeln("Flexiwind not initialized, Run init command first");
            return Command::FAILURE;
        }

        $this->store->init();

        foreach ($components as $component) {
            $this->addComponent($component, $namespace, $skipDeps);
        }

        $output->writeln('<fg=green>====== Everything installed ======</>');
        foreach ($this->createdFiles as $fileCreated) {
            $output->writeln("<fg=green>✓ Created : $fileCreated</>");
        }
        $output->writeln('<fg=green>====== Operation completed ======</>');

        return Command::SUCCESS;
    }

    private function configExists()
    {
        $configPath = $this->projectRoot . '/flexiwind.yaml';
        return file_exists($configPath);
    }

    private function loadConfiguration()
    {
        $configPath = $this->projectRoot . '/flexiwind.yaml';

        if (!file_exists($configPath)) {
            $this->registries = [];
            if ($this->output) {
                $this->output->writeln("Flexiwind not initialized, Run init command first");
            }
            return Command::FAILURE;
        }

        $config = Yaml::parseFile($configPath);
        $this->defaultSource = $config['defaultSource'] ?? Constants::LOCAL_REGISTRY;
        $this->registries = $config['registries'] ?? [];
    }

    private function addComponent(string $component, ?string $namespace, bool $skipDeps = false): void
    {
        $source = $this->determineSource($component, $namespace);
        $registryJson = $this->fetchRegistry($component, $source);

        if (!$registryJson) {
            $this->output->writeln("<fg=red>⚠️ Registry not found for component: {$component}</>");
            return;
        }

        if (!isset($registryJson['files']) || !is_array($registryJson['files'])) {
            $this->output->writeln("<fg=red>⚠️ Invalid registry: no files for {$component}</>");
            return;
        }

        $this->output->writeln("<fg=blue>Adding component: {$component}</>");

        // Handle registry dependencies first
        if (isset($registryJson['registryDependencies']) && is_array($registryJson['registryDependencies'])) {
            $this->handleRegistryDependencies($registryJson['registryDependencies'], $namespace);
        }

        // Handle package dependencies
        if (!$skipDeps) {
            $this->handlePackageDependencies($registryJson);
        }

        spin(message: "Processing files...", callback: function () use ($registryJson) {
            foreach ($registryJson['files'] as $file) {
                $this->processFile($file);
            }
        });

        if (isset($registryJson['patch']) && is_array($registryJson['patch'])) {
            foreach ($registryJson['patch'] as $targetFile => $patches) {
                foreach ($patches as $patch) {
                    // TODO: Implement patch application
                    // FileUtils::applyPatch($targetFile, $patch);
                }
            }
        }

        // Mark this component as installed
        $this->installedRegistryComponents[] = $component;
        $nameSpace = str_starts_with($component, '@')
            ? explode('/', $component)[0]
            : ($namespace ? $namespace : 'flexiwind');
        $this->store->add($component, $nameSpace, $registryJson['version']);
        $this->output->writeln("<fg=green>✔ {$component} added successfully</>");
    }

    private function handleRegistryDependencies(array $registryDependencies, ?string $namespace): void
    {
        foreach ($registryDependencies as $dependency) {
            // Skip if already installed in this session
            if (in_array($dependency, $this->installedRegistryComponents)) {
                continue;
            }

            // Check if it's already installed in the project
            if ($this->isRegistryComponentInstalled($dependency)) {
                $this->output->writeln("<fg=yellow>Registry dependency already installed: {$dependency}</>");

                continue;
            }

            $this->output->writeln("<fg=yellow>Installing registry dependency: {$dependency}</>");
            if ($this->skipPackageInstallation) {
                $this->showPendingCommands();
            }

            // Recursively add the dependency
            $this->addComponent($dependency, $namespace, false);
        }
    }

    private function handlePackageDependencies(array $registryJson): void
    {
        $dependencies = $registryJson['dependencies'] ?? [];
        $devDependencies = $registryJson['devDependencies'] ?? [];

        // Extract composer and node dependencies
        $composerDeps = array_merge(
            $dependencies['composer'] ?? [],
            $devDependencies['composer'] ?? []
        );
        $nodeDeps = array_merge(
            $dependencies['node'] ?? [],
            $devDependencies['node'] ?? []
        );

        if (empty($composerDeps) && empty($nodeDeps)) {
            return;
        }

        $allDeps = array_merge($composerDeps, $nodeDeps);
        if (count($composerDeps) > 0) {
            $this->output->writeln("<fg=yellow>Composer requires dependencies:</>");
            foreach ($composerDeps as $dep) {
                $this->output->writeln("<fg=yellow>     → {$dep}</>");
            }
        }
        if (count($nodeDeps) > 0) {
            $this->output->writeln("<fg=yellow>Node dependencies:</>");
            foreach ($nodeDeps as $dep) {
                $this->output->writeln("<fg=yellow>     → {$dep}</>");
            }
        }

        if (!confirm("Install dependencies now?", true)) {
            $this->output->writeln("<fg=red>Skipping dependency installation. You may need to install them manually.</>");
            $this->skipPackageInstallation = true;
            $this->savePendingCommands($dependencies, $devDependencies);
            return;
        }

        // Install Composer dependencies
        if (ProjectDetector::check_Composer($this->projectRoot) && !empty($composerDeps)) {
            $this->installComposerDependencies(
                $dependencies['composer'] ?? [],
                $devDependencies['composer'] ?? []
            );
        }

        // Install Node dependencies
        $packageManager = ProjectDetector::getNodePackageManager();
        if ($packageManager && file_exists($this->projectRoot . '/package.json') && !empty($nodeDeps)) {
            $this->installNodeDependencies(
                $dependencies['node'] ?? [],
                $devDependencies['node'] ?? [],
                $packageManager
            );
        }
    }

    private function installComposerDependencies(array $dependencies, array $devDependencies): void
    {
        if (empty($dependencies) && empty($devDependencies)) {
            return;
        }
        $composer = PackageInstaller::composer($this->projectRoot);
        $this->output->writeln("=================Installing Composer dependencies...=================");
        if (!empty($dependencies)) {
            foreach ($dependencies as $dep) {
                $packageName = $this->extractPackageName($dep);
                if (!$composer->isInstalled($packageName)) {
                    spin(
                        message: "Installing {$dep}...",
                        callback: fn() => $composer->install($dep, false)
                    );
                    $this->output->writeln("<fg=green>✓ Installed {$dep}</>");
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
                    $this->output->writeln("<fg=green>✓ Installed {$dep} (dev)</>");
                }
            }
        }
        $this->output->writeln("=================Composer dependencies installed=================");
    }

    private function installNodeDependencies(array $dependencies, array $devDependencies, string $packageManager): void
    {
        if (empty($dependencies) && empty($devDependencies)) {
            return;
        }

        $node = PackageInstaller::node($packageManager, $this->projectRoot);

        $this->output->writeln("=================Installing Node dependencies...=================");
        if (!empty($dependencies)) {
            foreach ($dependencies as $dep) {
                $packageName = $this->extractPackageName($dep);
                if (!$node->isInstalled($packageName)) {
                    spin(
                        message: "Installing {$dep}...",
                        callback: fn() => $node->install($dep, false)
                    );
                    $this->output->writeln("<fg=green>✓ Installed {$dep} (dev)</>");
                }
            }
        }

        if (!empty($devDependencies)) {
            $this->output->writeln("<fg=yellow>Installing Node.js dev dependencies...</>");
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

    private function isRegistryComponentInstalled(string $component): bool
    {
        $nameSpace = str_starts_with($component, '@')
            ? explode('/', $component)[0]
            : 'flexiwind';

        $installed = $this->store->exists($component, $nameSpace);
        return $installed;
    }


    private function extractPackageName(string $dependency): string
    {
        // Extract package name without version
        return explode('@', $dependency)[0];
    }

    private function determineSource(string $component, ?string $namespace): array
    {
        // If namespace is explicitly provided via --namespace option
        if ($namespace) {
            if (!isset($this->registries[$namespace])) {
                throw new \RuntimeException("Namespace {$namespace} not found in configuration.");
            }
            return $this->parseRegistryConfig($this->registries[$namespace]);
        }

        // If component starts with @namespace/
        if (str_starts_with($component, '@')) {
            $parts = explode('/', $component, 2);
            $prefix = $parts[0];

            if (!isset($this->registries[$prefix])) {
                throw new \RuntimeException("Namespace {$prefix} not found in configuration.");
            }
            return $this->parseRegistryConfig($this->registries[$prefix]);
        }

        // Default source for components without namespace
        return ['baseUrl' => $this->defaultSource];
    }

    private function parseRegistryConfig($config): array
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

        throw new \RuntimeException("Invalid registry configuration format");
    }

    private function expandEnvironmentVariables(array $headers): array
    {
        $expanded = [];
        foreach ($headers as $key => $value) {
            $expanded[$key] = preg_replace_callback('/\$\{([^}]+)\}/', function ($matches) {
                return $_ENV[$matches[1]] ?? $matches[0];
            }, $value);
        }
        return $expanded;
    }

    private function fetchRegistry(string $component, array $source): ?array
    {
        $componentName = str_starts_with($component, '@')
            ? explode('/', $component, 2)[1] ?? $component
            : $component;

        $url = str_replace('{name}', $componentName, $source['baseUrl']);
        $headers = $source['headers'] ?? [];
        $params = $source['params'] ?? [];

        $json = HttpUtils::getJson($url, $headers, $params);
        return is_array($json) ? $json : null;
    }

    private function processFile(array $file): void
    {
        $targetPath = $this->projectRoot . '/' . $file['target'];

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (file_exists($targetPath)) {
            $this->output->writeln("<fg=red>⚠️ File exists, skipping: {$file['target']}</>");
            return;
        }

        file_put_contents($targetPath, $file['content']);
        $this->createdFiles[] = $file['target'];
    }

    private function savePendingCommands(array $dependencies, array $devDependencies): void
    {
        $composerDeps = $dependencies['composer'] ?? [];
        $composerDevDeps = $devDependencies['composer'] ?? [];
        $nodeDeps = $dependencies['node'] ?? [];
        $nodeDevDeps = $devDependencies['node'] ?? [];

        // Generate Composer commands
        if (!empty($composerDeps) && ProjectDetector::check_Composer($this->projectRoot)) {
            $this->pendingCommands[] = 'composer require ' . implode(' ', array_map('escapeshellarg', $composerDeps));
        }

        if (!empty($composerDevDeps) && ProjectDetector::check_Composer($this->projectRoot)) {
            $this->pendingCommands[] = 'composer require --dev ' . implode(' ', array_map('escapeshellarg', $composerDevDeps));
        }

        // Generate Node commands
        $packageManager = ProjectDetector::getNodePackageManager();
        if ($packageManager && file_exists($this->projectRoot . '/package.json')) {
            if (!empty($nodeDeps)) {
                $this->pendingCommands[] = $this->buildNodeInstallCommand($nodeDeps, false, $packageManager);
            }

            if (!empty($nodeDevDeps)) {
                $this->pendingCommands[] = $this->buildNodeInstallCommand($nodeDevDeps, true, $packageManager);
            }
        }
    }

    private function buildNodeInstallCommand(array $packages, bool $isDevDep, string $packageManager): string
    {
        $escapedPackages = array_map('escapeshellarg', $packages);
        $packagesString = implode(' ', $escapedPackages);
        $command = PackageInstaller::node($packageManager)->buildInstallCommand($packagesString, $isDevDep);
        return $command;
    }

    private function showPendingCommands(): void
    {
        if (count($this->pendingCommands) > 0) {
            $this->output->writeln("📋 Manual installation required");
            $this->output->writeln("Run the following commands manually to install dependencies:");
            foreach ($this->pendingCommands as $command) {
                $this->output->writeln("  {$command}");
            }
        }
    }
}
