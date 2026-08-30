<?php

namespace FlexiCli\Command;

use FlexiCore\Core\{Constants, RegistryComponentReference, RegistryVersionResolver};
use FlexiCore\Service\{RegistryListService, ComponentPreviewService};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\confirm;

class PreviewCommand extends Command
{
    private string $projectRoot;
    private array $registries = [];
    private string $defaultSource;

    public function __construct(
        private RegistryListService $listService = new RegistryListService(),
        private ComponentPreviewService $previewService = new ComponentPreviewService(),
        private RegistryVersionResolver $versionResolver = new RegistryVersionResolver()
    ) {
        parent::__construct();
        $this->projectRoot = getcwd();
        $this->loadConfiguration();
    }

    protected function configure(): void
    {
        $this
            ->setName('preview')
            ->setDescription('Preview a component before installation')
            ->addArgument('component', InputArgument::REQUIRED, 'Component reference (button, @flexiwind/button@1.0.0)')
            ->addOption('files', 'f', InputOption::VALUE_NONE, 'Show file contents')
            ->addOption('install', null, InputOption::VALUE_NONE, 'Install after preview if confirmed')
            ->addOption('no-confirm', null, InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $componentInput = $input->getArgument('component');

        try {
            $reference = RegistryComponentReference::parse($componentInput);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<fg=red>Invalid component reference: ' . $e->getMessage() . '</>');
            return Command::FAILURE;
        }

        $source = $this->determineSource($reference);
        if (!$source) {
            $output->writeln('<fg=red>Registry not found for: ' . $reference->toDisplay() . '</>');
            return Command::FAILURE;
        }

        $resolved = $this->versionResolver->resolve(
            $source['baseUrl'],
            $reference->componentName,
            $reference->version,
            $source['headers'] ?? [],
            $source['params'] ?? []
        );

        if (!$resolved) {
            $output->writeln('<fg=red>Component not found: ' . $reference->toDisplay() . '</>');
            return Command::FAILURE;
        }

        $component = $resolved['registry'];

        // Generate preview
        $preview = $this->previewService->preview($component, [
            'show_files' => (bool) $input->getOption('files'),
        ]);

        // Display
        $output->writeln('<fg=blue>Component Preview</>');
        $output->writeln('');
        $output->writeln($this->previewService->formatPreview($preview));
        $output->writeln('');

        if ($input->getOption('files') && !empty($component['files'])) {
            $output->writeln('<fg=yellow>File Contents:</>');
            $output->writeln('');
            foreach (array_slice($component['files'], 0, 3) as $file) {
                $output->writeln($this->previewService->showFilePreview($file, 15));
                $output->writeln('');
            }
        }

        // Install prompt
        if ($input->getOption('install') || (!$input->getOption('no-confirm') && confirm('Install this component?'))) {
            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }

    private function determineSource(RegistryComponentReference $reference): ?array
    {
        if ($reference->namespace !== null) {
            if (isset($this->registries[$reference->namespace])) {
                $config = $this->registries[$reference->namespace];
                if (is_string($config)) {
                    return ['baseUrl' => $config];
                }
                return ['baseUrl' => $config['url'] ?? '', 'headers' => $config['headers'] ?? []];
            }
        }

        return ['baseUrl' => $this->defaultSource];
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
}
