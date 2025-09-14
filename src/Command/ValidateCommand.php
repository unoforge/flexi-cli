<?php

namespace FlexiCli\Command;

use FlexiCli\Core\{SchemaValidator, Constants};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\{info, error, note};

class ValidateCommand extends Command
{
    protected static $defaultName = 'validate';

    protected function configure()
    {
        $this->setName('validate')
            ->setDescription('Validate registry items against the schema')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Path to the registry file to validate (defaults to registry.json)',
                'registry.json'
            )
            ->addOption(
                'item',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Validate a specific item by name (for registry.json with multiple items)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('file') ?? 'registry.json';
        $schemaPath = Constants::SCHEMA_URL;
        $itemName = $input->getOption('item');

        if (!file_exists($filePath)) {
            error("File not found: $filePath");
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($filePath), true);
        if (!$data) {
            error("Invalid JSON file: $filePath");
            return Command::FAILURE;
        }

        try {
            $validator = new SchemaValidator($schemaPath);
        } catch (\Exception $e) {
            error("Schema validation error: " . $e->getMessage());
            return Command::FAILURE;
        }

        note("🔍 Validating registry items against schema...");

        $hasErrors = false;

        // Handle different file structures
        if ($this->isRegistryFile($data)) {
            // This is a registry.json file with multiple items
            $items = $data['items'] ?? [];
            
            if ($itemName) {
                // Validate specific item
                $item = $this->findItemByName($items, $itemName);
                if (!$item) {
                    error("Item '$itemName' not found in registry");
                    return Command::FAILURE;
                }
                
                $hasErrors = !$this->validateSingleItem($validator, $item, $itemName);
            } else {
                // Validate all items
                foreach ($items as $index => $item) {
                    $name = $item['name'] ?? "item-$index";
                    if (!$this->validateSingleItem($validator, $item, $name)) {
                        $hasErrors = true;
                    }
                }
            }
        } else {
            // This is a single registry item file
            $itemName = $itemName ?? ($data['name'] ?? 'unknown');
            $hasErrors = !$this->validateSingleItem($validator, $data, $itemName);
        }

        if ($hasErrors) {
            $output->writeln("<fg=red>✘ Validation completed with errors</>");
            return Command::FAILURE;
        }

        info("✅ All registry items are valid!");
        return Command::SUCCESS;
    }

    private function isRegistryFile(array $data): bool
    {
        return isset($data['items']) && is_array($data['items']);
    }

    private function findItemByName(array $items, string $name): ?array
    {
        foreach ($items as $item) {
            if (isset($item['name']) && $item['name'] === $name) {
                return $item;
            }
        }
        return null;
    }

    private function validateSingleItem(SchemaValidator $validator, array $item, string $name): bool
    {
        info("Validating: $name");
        
        $isValid = $validator->validate($item);
        
        if (!$isValid) {
            error("✘ Validation failed for: $name");
            $errors = $validator->getErrors();
            foreach ($errors as $error) {
                error("  • $error");
            }
            return false;
        }

        info("✅ Valid: $name");
        return true;
    }
}
