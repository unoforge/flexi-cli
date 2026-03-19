<?php

namespace FlexiCli\Command;

use FlexiCore\Core\Constants;
use FlexiCore\Core\IconMapping;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\{info, error, warning, spin};

class FixIcons extends Command
{
    private string $projectRoot;
    private ?OutputInterface $output = null;
    public function __construct()
    {
        parent::__construct();
        $this->projectRoot = getcwd();
    }

    protected function configure(): void
    {
        $this
            ->setName('fix-icons')
            ->setDescription('Replace Phosphor icons inside component/ui with icons from the configured icon library');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        // Check if flexiwind.yaml exists
        $configPath = $this->projectRoot . '/flexiwind.yaml';
        if (!file_exists($configPath)) {
            error('flexiwind.yaml not found. Please run init command first.');
            return Command::FAILURE;
        }

        // Load configuration
        $config = Yaml::parseFile($configPath);
        $iconLibrary = $config['iconLibrary'] ?? null;

        if (!$iconLibrary) {
            error('iconLibrary not found in flexiwind.yaml. Please configure an icon library first.');
            return Command::FAILURE;
        }

        $iconLibraryLower = strtolower($iconLibrary);

        // Check if user has Phosphor as icon library
        if ($iconLibraryLower === 'phosphore') {
            info('Phosphor is already configured as the icon library. No replacement needed.');
            return Command::SUCCESS;
        }

        // Validate icon library
        if (!in_array($iconLibraryLower, array_map('strtolower', Constants::ICON_LIBRARIES))) {
            error("Unsupported icon library: {$iconLibrary}");
            return Command::FAILURE;
        }

        // Get icon prefix for the target library
        $targetPrefix = Constants::UI_ICONS[$iconLibraryLower] ?? null;
        if (!$targetPrefix) {
            error("Could not determine icon prefix for library: {$iconLibrary}");
            return Command::FAILURE;
        }

        // Scan components folder
        $componentsPath = $this->projectRoot . '/resources/views/components/ui';
        if (!is_dir($componentsPath)) {
            warning("Components directory not found: {$componentsPath}");
            return Command::SUCCESS;
        }

        info("Scanning components in: {$componentsPath}");
        info("Replacing Phosphor icons with {$iconLibrary} icons...");

        // Determine mapping key based on icon library
        $mappingKey = null;
        if ($iconLibraryLower === 'heroicons') {
            $mappingKey = 'heroicons';
        } elseif ($iconLibraryLower === 'lucide') {
            $mappingKey = 'lucide';
        } elseif ($iconLibraryLower === 'hugeicons') {
            $mappingKey = 'hugeicons';
        }

        if (!$mappingKey) {
            error("Could not determine mapping key for library: {$iconLibrary}");
            return Command::FAILURE;
        }

        $filesProcessed = 0;
        $iconsReplaced = 0;

        spin(
            message: 'Processing components...',
            callback: function () use ($componentsPath, $mappingKey, &$filesProcessed, &$iconsReplaced) {
                $files = $this->getComponentFiles($componentsPath);
                
                foreach ($files as $file) {
                    $content = file_get_contents($file);
                    if ($content === false) {
                        continue;
                    }

                    $originalContent = $content;
                    $fileIconsReplaced = 0;

                    // Replace each Phosphor icon with the target library equivalent
                    foreach (IconMapping::MAP as $phosphorIcon => $mappings) {
                        if (!isset($mappings[$mappingKey])) {
                            continue;
                        }

                        $targetIcon = $mappings[$mappingKey];
                        $pattern = '/' . preg_quote($phosphorIcon, '/') . '/';
                        
                        if (preg_match($pattern, $content)) {
                            $content = preg_replace($pattern, $targetIcon, $content);
                            $fileIconsReplaced++;
                        }
                    }

                    if ($content !== $originalContent) {
                        file_put_contents($file, $content);
                        $filesProcessed++;
                        $iconsReplaced += $fileIconsReplaced;
                    }
                }
            }
        );

        if ($filesProcessed > 0) {
            info("✓ Processed {$filesProcessed} file(s)");
            info("✓ Replaced {$iconsReplaced} icon(s)");
        } else {
            info("No Phosphor icons found to replace.");
        }

        return Command::SUCCESS;
    }

    /**
     * Get all component files recursively from the components directory
     *
     * @param string $directory
     * @return array
     */
    private function getComponentFiles(string $directory): array
    {
        $files = [];
        
        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(blade\.php|php|html|twig)$/', $file->getFilename())) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
}
