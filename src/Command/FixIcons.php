<?php

namespace FlexiCli\Command;

use FlexiCli\Core\Constants;
use FlexiCli\Core\FileEditor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\{info, error, warning, spin};

class FixIcons extends Command
{
    private string $projectRoot;
    private ?OutputInterface $output = null;
    
    /**
     * Icon mapping: Phosphor icons to their equivalents in other libraries
     * Format: ['phosphor_icon_name' => ['heroicons' => 'heroicons_icon', 'lucide' => 'lucide_icon', 'hugeicons' => 'hugeicons_icon']]
     */
    private const ICON_MAPPING = [
        // User icons
        'ph--user' => ['heroicons' => 'heroicons--user', 'lucide' => 'lucide--user', 'hugeicons' => 'hugeicons--user'],
        'ph--user-circle' => ['heroicons' => 'heroicons--user-circle', 'lucide' => 'lucide--user-circle', 'hugeicons' => 'hugeicons--user-circle'],
        'ph--user-bold' => ['heroicons' => 'heroicons--user-solid', 'lucide' => 'lucide--user', 'hugeicons' => 'hugeicons--user'],
        
        // Search icons
        'ph--magnifying-glass' => ['heroicons' => 'heroicons--magnifying-glass', 'lucide' => 'lucide--search', 'hugeicons' => 'hugeicons--search'],
        'ph--magnifying-glass-bold' => ['heroicons' => 'heroicons--magnifying-glass', 'lucide' => 'lucide--search', 'hugeicons' => 'hugeicons--search'],
        
        // Notification icons
        'ph--bell' => ['heroicons' => 'heroicons--bell', 'lucide' => 'lucide--bell', 'hugeicons' => 'hugeicons--notification'],
        'ph--bell-ringing' => ['heroicons' => 'heroicons--bell-alert', 'lucide' => 'lucide--bell-ring', 'hugeicons' => 'hugeicons--notification'],
        'ph--bell-bold' => ['heroicons' => 'heroicons--bell', 'lucide' => 'lucide--bell', 'hugeicons' => 'hugeicons--notification'],
        
        // Home icons
        'ph--house' => ['heroicons' => 'heroicons--home', 'lucide' => 'lucide--home', 'hugeicons' => 'hugeicons--home'],
        'ph--house-simple' => ['heroicons' => 'heroicons--home', 'lucide' => 'lucide--home', 'hugeicons' => 'hugeicons--home'],
        'ph--house-bold' => ['heroicons' => 'heroicons--home', 'lucide' => 'lucide--home', 'hugeicons' => 'hugeicons--home'],
        
        // Settings icons
        'ph--gear' => ['heroicons' => 'heroicons--cog-6-tooth', 'lucide' => 'lucide--settings', 'hugeicons' => 'hugeicons--settings'],
        'ph--gear-six' => ['heroicons' => 'heroicons--cog-6-tooth', 'lucide' => 'lucide--settings', 'hugeicons' => 'hugeicons--settings'],
        'ph--gear-bold' => ['heroicons' => 'heroicons--cog-6-tooth', 'lucide' => 'lucide--settings', 'hugeicons' => 'hugeicons--settings'],
        
        // Sign out icons
        'ph--sign-out' => ['heroicons' => 'heroicons--arrow-right-on-rectangle', 'lucide' => 'lucide--log-out', 'hugeicons' => 'hugeicons--logout'],
        'ph--sign-out-bold' => ['heroicons' => 'heroicons--arrow-right-on-rectangle', 'lucide' => 'lucide--log-out', 'hugeicons' => 'hugeicons--logout'],
        
        // Plus/Minus icons
        'ph--plus' => ['heroicons' => 'heroicons--plus', 'lucide' => 'lucide--plus', 'hugeicons' => 'hugeicons--plus'],
        'ph--plus-bold' => ['heroicons' => 'heroicons--plus', 'lucide' => 'lucide--plus', 'hugeicons' => 'hugeicons--plus'],
        'ph--minus' => ['heroicons' => 'heroicons--minus', 'lucide' => 'lucide--minus', 'hugeicons' => 'hugeicons--minus'],
        'ph--minus-bold' => ['heroicons' => 'heroicons--minus', 'lucide' => 'lucide--minus', 'hugeicons' => 'hugeicons--minus'],
        
        // Close/Check icons
        'ph--x' => ['heroicons' => 'heroicons--x-mark', 'lucide' => 'lucide--x', 'hugeicons' => 'hugeicons--close'],
        'ph--x-bold' => ['heroicons' => 'heroicons--x-mark', 'lucide' => 'lucide--x', 'hugeicons' => 'hugeicons--close'],
        'ph--check' => ['heroicons' => 'heroicons--check', 'lucide' => 'lucide--check', 'hugeicons' => 'hugeicons--check'],
        'ph--check-bold' => ['heroicons' => 'heroicons--check', 'lucide' => 'lucide--check', 'hugeicons' => 'hugeicons--check'],
        
        // Arrow icons
        'ph--arrow-left' => ['heroicons' => 'heroicons--arrow-left', 'lucide' => 'lucide--arrow-left', 'hugeicons' => 'hugeicons--arrow-left'],
        'ph--arrow-right' => ['heroicons' => 'heroicons--arrow-right', 'lucide' => 'lucide--arrow-right', 'hugeicons' => 'hugeicons--arrow-right'],
        'ph--arrow-up' => ['heroicons' => 'heroicons--arrow-up', 'lucide' => 'lucide--arrow-up', 'hugeicons' => 'hugeicons--arrow-up'],
        'ph--arrow-down' => ['heroicons' => 'heroicons--arrow-down', 'lucide' => 'lucide--arrow-down', 'hugeicons' => 'hugeicons--arrow-down'],
        'ph--arrow-left-bold' => ['heroicons' => 'heroicons--arrow-left', 'lucide' => 'lucide--arrow-left', 'hugeicons' => 'hugeicons--arrow-left'],
        'ph--arrow-right-bold' => ['heroicons' => 'heroicons--arrow-right', 'lucide' => 'lucide--arrow-right', 'hugeicons' => 'hugeicons--arrow-right'],
        
        // Caret icons
        'ph--caret-left' => ['heroicons' => 'heroicons--chevron-left', 'lucide' => 'lucide--chevron-left', 'hugeicons' => 'hugeicons--arrow-left'],
        'ph--caret-right' => ['heroicons' => 'heroicons--chevron-right', 'lucide' => 'lucide--chevron-right', 'hugeicons' => 'hugeicons--arrow-right'],
        'ph--caret-up' => ['heroicons' => 'heroicons--chevron-up', 'lucide' => 'lucide--chevron-up', 'hugeicons' => 'hugeicons--arrow-up'],
        'ph--caret-down' => ['heroicons' => 'heroicons--chevron-down', 'lucide' => 'lucide--chevron-down', 'hugeicons' => 'hugeicons--arrow-down'],
        
        // Delete/Edit icons
        'ph--trash' => ['heroicons' => 'heroicons--trash', 'lucide' => 'lucide--trash-2', 'hugeicons' => 'hugeicons--trash'],
        'ph--trash-bold' => ['heroicons' => 'heroicons--trash', 'lucide' => 'lucide--trash-2', 'hugeicons' => 'hugeicons--trash'],
        'ph--pencil' => ['heroicons' => 'heroicons--pencil', 'lucide' => 'lucide--pencil', 'hugeicons' => 'hugeicons--edit'],
        'ph--pencil-simple' => ['heroicons' => 'heroicons--pencil', 'lucide' => 'lucide--pencil', 'hugeicons' => 'hugeicons--edit'],
        'ph--pencil-bold' => ['heroicons' => 'heroicons--pencil', 'lucide' => 'lucide--pencil', 'hugeicons' => 'hugeicons--edit'],
        
        // Visibility icons
        'ph--eye' => ['heroicons' => 'heroicons--eye', 'lucide' => 'lucide--eye', 'hugeicons' => 'hugeicons--eye'],
        'ph--eye-slash' => ['heroicons' => 'heroicons--eye-slash', 'lucide' => 'lucide--eye-off', 'hugeicons' => 'hugeicons--eye-closed'],
        'ph--eye-bold' => ['heroicons' => 'heroicons--eye', 'lucide' => 'lucide--eye', 'hugeicons' => 'hugeicons--eye'],
        
        // Lock icons
        'ph--lock' => ['heroicons' => 'heroicons--lock-closed', 'lucide' => 'lucide--lock', 'hugeicons' => 'hugeicons--lock'],
        'ph--lock-key' => ['heroicons' => 'heroicons--key', 'lucide' => 'lucide--key', 'hugeicons' => 'hugeicons--key'],
        'ph--lock-bold' => ['heroicons' => 'heroicons--lock-closed', 'lucide' => 'lucide--lock', 'hugeicons' => 'hugeicons--lock'],
        'ph--unlock' => ['heroicons' => 'heroicons--lock-open', 'lucide' => 'lucide--unlock', 'hugeicons' => 'hugeicons--unlock'],
        
        // Communication icons
        'ph--envelope' => ['heroicons' => 'heroicons--envelope', 'lucide' => 'lucide--mail', 'hugeicons' => 'hugeicons--mail'],
        'ph--envelope-simple' => ['heroicons' => 'heroicons--envelope', 'lucide' => 'lucide--mail', 'hugeicons' => 'hugeicons--mail'],
        'ph--envelope-bold' => ['heroicons' => 'heroicons--envelope', 'lucide' => 'lucide--mail', 'hugeicons' => 'hugeicons--mail'],
        'ph--key' => ['heroicons' => 'heroicons--key', 'lucide' => 'lucide--key', 'hugeicons' => 'hugeicons--key'],
        'ph--key-bold' => ['heroicons' => 'heroicons--key', 'lucide' => 'lucide--key', 'hugeicons' => 'hugeicons--key'],
        
        // Date/Time icons
        'ph--calendar' => ['heroicons' => 'heroicons--calendar', 'lucide' => 'lucide--calendar', 'hugeicons' => 'hugeicons--calendar'],
        'ph--calendar-blank' => ['heroicons' => 'heroicons--calendar', 'lucide' => 'lucide--calendar', 'hugeicons' => 'hugeicons--calendar'],
        'ph--calendar-bold' => ['heroicons' => 'heroicons--calendar', 'lucide' => 'lucide--calendar', 'hugeicons' => 'hugeicons--calendar'],
        'ph--clock' => ['heroicons' => 'heroicons--clock', 'lucide' => 'lucide--clock', 'hugeicons' => 'hugeicons--clock'],
        'ph--clock-bold' => ['heroicons' => 'heroicons--clock', 'lucide' => 'lucide--clock', 'hugeicons' => 'hugeicons--clock'],
        
        // Media icons
        'ph--image' => ['heroicons' => 'heroicons--photo', 'lucide' => 'lucide--image', 'hugeicons' => 'hugeicons--image'],
        'ph--image-bold' => ['heroicons' => 'heroicons--photo', 'lucide' => 'lucide--image', 'hugeicons' => 'hugeicons--image'],
        'ph--file' => ['heroicons' => 'heroicons--document', 'lucide' => 'lucide--file', 'hugeicons' => 'hugeicons--file'],
        'ph--file-text' => ['heroicons' => 'heroicons--document-text', 'lucide' => 'lucide--file-text', 'hugeicons' => 'hugeicons--file'],
        'ph--file-bold' => ['heroicons' => 'heroicons--document', 'lucide' => 'lucide--file', 'hugeicons' => 'hugeicons--file'],
        'ph--folder' => ['heroicons' => 'heroicons--folder', 'lucide' => 'lucide--folder', 'hugeicons' => 'hugeicons--folder'],
        'ph--folder-open' => ['heroicons' => 'heroicons--folder-open', 'lucide' => 'lucide--folder-open', 'hugeicons' => 'hugeicons--folder-open'],
        
        // Transfer icons
        'ph--download' => ['heroicons' => 'heroicons--arrow-down-tray', 'lucide' => 'lucide--download', 'hugeicons' => 'hugeicons--download'],
        'ph--download-bold' => ['heroicons' => 'heroicons--arrow-down-tray', 'lucide' => 'lucide--download', 'hugeicons' => 'hugeicons--download'],
        'ph--upload' => ['heroicons' => 'heroicons--arrow-up-tray', 'lucide' => 'lucide--upload', 'hugeicons' => 'hugeicons--upload'],
        'ph--upload-bold' => ['heroicons' => 'heroicons--arrow-up-tray', 'lucide' => 'lucide--upload', 'hugeicons' => 'hugeicons--upload'],
        
        // Link/Share icons
        'ph--link' => ['heroicons' => 'heroicons--link', 'lucide' => 'lucide--link', 'hugeicons' => 'hugeicons--link'],
        'ph--link-bold' => ['heroicons' => 'heroicons--link', 'lucide' => 'lucide--link', 'hugeicons' => 'hugeicons--link'],
        'ph--share' => ['heroicons' => 'heroicons--share', 'lucide' => 'lucide--share-2', 'hugeicons' => 'hugeicons--share'],
        'ph--share-network' => ['heroicons' => 'heroicons--share', 'lucide' => 'lucide--share-2', 'hugeicons' => 'hugeicons--share'],
        
        // Favorite icons
        'ph--heart' => ['heroicons' => 'heroicons--heart', 'lucide' => 'lucide--heart', 'hugeicons' => 'hugeicons--heart'],
        'ph--heart-bold' => ['heroicons' => 'heroicons--heart', 'lucide' => 'lucide--heart', 'hugeicons' => 'hugeicons--heart'],
        'ph--star' => ['heroicons' => 'heroicons--star', 'lucide' => 'lucide--star', 'hugeicons' => 'hugeicons--star'],
        'ph--star-bold' => ['heroicons' => 'heroicons--star', 'lucide' => 'lucide--star', 'hugeicons' => 'hugeicons--star'],
        
        // Menu icons
        'ph--menu' => ['heroicons' => 'heroicons--bars-3', 'lucide' => 'lucide--menu', 'hugeicons' => 'hugeicons--menu'],
        'ph--list' => ['heroicons' => 'heroicons--list-bullet', 'lucide' => 'lucide--list', 'hugeicons' => 'hugeicons--list'],
        'ph--grid' => ['heroicons' => 'heroicons--squares-2x2', 'lucide' => 'lucide--grid-3x3', 'hugeicons' => 'hugeicons--grid'],
        'ph--squares-four' => ['heroicons' => 'heroicons--squares-2x2', 'lucide' => 'lucide--grid-3x3', 'hugeicons' => 'hugeicons--grid'],
        'ph--dots-three' => ['heroicons' => 'heroicons--ellipsis-horizontal', 'lucide' => 'lucide--more-horizontal', 'hugeicons' => 'hugeicons--more-horizontal'],
        'ph--dots-three-vertical' => ['heroicons' => 'heroicons--ellipsis-vertical', 'lucide' => 'lucide--more-vertical', 'hugeicons' => 'hugeicons--more-vertical'],
        
        // Alert icons
        'ph--warning' => ['heroicons' => 'heroicons--exclamation-triangle', 'lucide' => 'lucide--alert-triangle', 'hugeicons' => 'hugeicons--warning'],
        'ph--warning-bold' => ['heroicons' => 'heroicons--exclamation-triangle', 'lucide' => 'lucide--alert-triangle', 'hugeicons' => 'hugeicons--warning'],
        'ph--info' => ['heroicons' => 'heroicons--information-circle', 'lucide' => 'lucide--info', 'hugeicons' => 'hugeicons--info'],
        'ph--info-bold' => ['heroicons' => 'heroicons--information-circle', 'lucide' => 'lucide--info', 'hugeicons' => 'hugeicons--info'],
        'ph--question' => ['heroicons' => 'heroicons--question-mark-circle', 'lucide' => 'lucide--help-circle', 'hugeicons' => 'hugeicons--question'],
        'ph--question-bold' => ['heroicons' => 'heroicons--question-mark-circle', 'lucide' => 'lucide--help-circle', 'hugeicons' => 'hugeicons--question'],
        
        // Shape icons
        'ph--circle' => ['heroicons' => 'heroicons--circle', 'lucide' => 'lucide--circle', 'hugeicons' => 'hugeicons--circle'],
        'ph--circle-filled' => ['heroicons' => 'heroicons--check-circle', 'lucide' => 'lucide--circle', 'hugeicons' => 'hugeicons--circle'],
        'ph--square' => ['heroicons' => 'heroicons--square-3-stack-3d', 'lucide' => 'lucide--square', 'hugeicons' => 'hugeicons--square'],
        'ph--square-filled' => ['heroicons' => 'heroicons--square-3-stack-3d', 'lucide' => 'lucide--square', 'hugeicons' => 'hugeicons--square'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->projectRoot = getcwd();
    }

    protected function configure(): void
    {
        $this
            ->setName('fix-icons')
            ->setDescription('Replace Phosphor icons with icons from the configured icon library');
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
                    foreach (self::ICON_MAPPING as $phosphorIcon => $mappings) {
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
