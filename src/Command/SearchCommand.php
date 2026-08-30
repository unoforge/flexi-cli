<?php

namespace FlexiCli\Command;

use FlexiCore\Core\Constants;
use FlexiCore\Service\{RegistryListService, RegistrySearchService};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SearchCommand extends Command
{
    private string $projectRoot;
    private array $registries = [];
    private string $defaultSource;

    public function __construct(
        private RegistrySearchService $searchService = new RegistrySearchService(),
        private RegistryListService $listService = new RegistryListService()
    ) {
        parent::__construct();
        $this->projectRoot = getcwd();
        $this->loadConfiguration();
    }

    protected function configure(): void
    {
        $this
            ->setName('search')
            ->setDescription('Search for components in registries')
            ->addArgument('query', InputArgument::REQUIRED, 'Search query (component name, description)')
            ->addOption('registry', 'r', InputOption::VALUE_REQUIRED, 'Search in specific registry')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Filter by type (e.g., registry:ui)')
            ->addOption('min-version', null, InputOption::VALUE_REQUIRED, 'Minimum version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $input->getArgument('query');
        $registryFilter = $input->getOption('registry');

        $filters = [];
        if ($input->getOption('type')) {
            $filters['type'] = $input->getOption('type');
        }
        if ($input->getOption('min-version')) {
            $filters['version'] = $input->getOption('min-version');
        }

        // Get components
        $allComponents = [];
        $registryMap = [];

        if ($registryFilter) {
            $result = $this->listService->listComponents($registryFilter, [], [], []);
            if ($result['success']) {
                $allComponents = $result['components'];
                foreach ($allComponents as $comp) {
                    $registryMap[$comp['name']] = $registryFilter;
                }
            }
        } else {
            // Search across all registries
            foreach ($this->registries as $name => $config) {
                $result = $this->listService->listComponents($name, [], [], []);
                if ($result['success']) {
                    foreach ($result['components'] as $comp) {
                        $allComponents[] = $comp;
                        $registryMap[$comp['name']] = $name;
                    }
                }
            }
        }

        if (empty($allComponents)) {
            $output->writeln('<fg=yellow>No registries configured or accessible.</>');
            return Command::FAILURE;
        }

        // Search
        $result = $this->searchService->search($query, $allComponents, $filters);

        if (!$result['success']) {
            $output->writeln('<fg=red>' . $result['message'] . '</>');
            return Command::FAILURE;
        }

        if (empty($result['results'])) {
            $output->writeln('<fg=yellow>No components found for: ' . $query . '</></>');
            return Command::SUCCESS;
        }

        // Display
        $output->writeln('<fg=blue>Search Results</>');
        $output->writeln('');

        if ($registryFilter) {
            $formatted = $this->searchService->formatResults($result['results'], $query);
        } else {
            $grouped = $this->searchService->groupByRegistry($result['results'], $registryMap);
            $formatted = $this->searchService->formatGroupedResults($grouped, $query);
        }

        $output->writeln($formatted);

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
