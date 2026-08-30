<?php

namespace FlexiCli\Command;

use FlexiCore\Core\{Constants, GitHubComponentReference, RegistryVersionResolver};
use FlexiCore\Service\{RegistryListService, GitHubRegistryResolver};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ListCommand extends Command
{
    private string $projectRoot;
    private array $registries = [];
    private string $defaultSource;

    public function __construct(
        private RegistryListService $listService = new RegistryListService(),
        private RegistryVersionResolver $versionResolver = new RegistryVersionResolver()
    ) {
        parent::__construct();
        $this->projectRoot = getcwd();
        $this->loadConfiguration();
    }

    protected function configure(): void
    {
        $this
            ->setName('list')
            ->setDescription('List all components from a registry')
            ->addArgument('registry', InputArgument::OPTIONAL, 'Registry to list from (e.g., @flexiwind, local-key)')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Filter by component type (e.g., registry:ui)')
            ->addOption('search', 's', InputOption::VALUE_REQUIRED, 'Search for specific component')
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, 'Sort by: name (default), version, type')
            ->addOption('show-files', null, InputOption::VALUE_NONE, 'Show file list for each component')
            ->addOption('show-deps', null, InputOption::VALUE_NONE, 'Show dependencies for each component');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registry = $input->getArgument('registry');

        if (!$registry) {
            return $this->listAllRegistries($output);
        }

        // Check if it's a GitHub reference
        if (GitHubComponentReference::isGitHubReference($registry)) {
            $ghRef = GitHubComponentReference::parse($registry);
            if ($ghRef) {
                return $this->listGitHubRegistry($output, $ghRef, $input);
            }
        }

        $source = $this->resolveRegistrySource($registry);

        if (!$source) {
            $output->writeln("<fg=red>✗ Registry not found: {$registry}</>");
            return Command::FAILURE;
        }

        $filters = [];
        if ($input->getOption('type')) {
            $filters['type'] = $input->getOption('type');
        }
        if ($input->getOption('search')) {
            $filters['search'] = $input->getOption('search');
        }
        if ($input->getOption('sort')) {
            $filters['sort'] = $input->getOption('sort');
        }

        $options = [
            'show_files' => (bool) $input->getOption('show-files'),
            'show_deps' => (bool) $input->getOption('show-deps'),
        ];

        $result = $this->listService->listComponents(
            $registry,
            $filters,
            $source['headers'] ?? [],
            $source['params'] ?? []
        );

        if (!$result['success']) {
            $output->writeln("<fg=yellow>{$result['message']}</>");
            return Command::SUCCESS;
        }

        $this->displayRegistry($output, $registry, $result, $options);

        return Command::SUCCESS;
    }

    private function listAllRegistries(OutputInterface $output): int
    {
        $output->writeln('<fg=blue>📦 Available Registries</>');
        $output->writeln('');

        if (empty($this->registries)) {
            $output->writeln('<fg=yellow>No registries configured. Run "flexi-cli init" first.</>');
            return Command::SUCCESS;
        }

        foreach ($this->registries as $name => $config) {
            $url = is_string($config) ? $config : ($config['url'] ?? '');
            $output->writeln("<fg=green>✓</> <fg=cyan>{$name}</> - {$url}");
        }

        $output->writeln('');
        $output->writeln('Use: <fg=cyan>flexi-cli list {registry-name}</> to see components');

        return Command::SUCCESS;
    }

    private function displayRegistry(
        OutputInterface $output,
        string $registry,
        array $result,
        array $options
    ): void
    {
        $output->writeln('<fg=blue>[REGISTRY] ' . $registry . ' Components</>');
        $output->writeln('');

        $formatted = $this->listService->formatForDisplay($result['components'], $options);
        $output->writeln($formatted);

        $output->writeln('');
        $stats = $this->listService->getStatistics($result['components']);
        $total = $stats['total'];
        $output->writeln("<fg=yellow>Total: " . $total . " component(s)</>");

        if (!empty($stats['by_type'])) {
            $output->writeln('');
            foreach ($stats['by_type'] as $type => $count) {
                $output->writeln("  • " . $type . ": " . $count);
            }
        }
    }

    private function resolveRegistrySource(string $registry): ?array
    {
        // Check if it's a configured registry
        if (isset($this->registries[$registry])) {
            $config = $this->registries[$registry];
            if (is_string($config)) {
                return ['baseUrl' => $config];
            }
            return ['baseUrl' => $config['url'] ?? '', 'headers' => $config['headers'] ?? []];
        }

        // Check if it's the default registry with a namespace
        return ['baseUrl' => $this->defaultSource];
    }

    private function listGitHubRegistry(OutputInterface $output, GitHubComponentReference $ghRef, InputInterface $input): int
    {
        $output->writeln('<fg=blue>[GITHUB] ' . $ghRef->toDisplayWithPrefix() . '</>');
        $output->writeln('');

        $resolver = new GitHubRegistryResolver();

        // Get repo info
        $repoInfo = $resolver->getRepoInfo($ghRef);
        if (!$repoInfo || isset($repoInfo['message'])) {
            $output->writeln('<fg=red>Repository not found: ' . $ghRef->toDisplay() . '</>');
            return Command::FAILURE;
        }

        $output->writeln('Repository: ' . $repoInfo['full_name'] ?? $ghRef->toDisplay());
        if (isset($repoInfo['description'])) {
            $output->writeln('Description: ' . $repoInfo['description']);
        }
        $output->writeln('Branch: ' . $ghRef->branch);
        $output->writeln('');

        // List components
        $components = $resolver->listComponents($ghRef);

        if (empty($components)) {
            $output->writeln('<fg=yellow>No components found in this repository.</>');
            return Command::SUCCESS;
        }

        $options = [
            'show_files' => (bool) $input->getOption('show-files'),
            'show_deps' => (bool) $input->getOption('show-deps'),
        ];

        $formatted = $this->listService->formatForDisplay($components, $options);
        $output->writeln($formatted);

        $output->writeln('');
        $stats = $this->listService->getStatistics($components);
        $output->writeln('<fg=yellow>Total: ' . $stats['total'] . ' component(s)</>');

        return Command::SUCCESS;
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
