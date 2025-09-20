<?php

namespace FlexiCli\Libs;

use FlexiCli\Core\{ConfigWriter, FileGenerator};
use FlexiCli\Installer\PackageInstaller;
use FlexiCli\Installer\{LivewireInstaller, AlpineInstaller, StimulusInstaller, UnoCSSInstaller, TailwindInstaller, IconLibraryInstaller};
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\{spin, text};

class FlexiwindInitializer
{
    private array $completedActions = [];

    public function initialize(
        string $projectType,
        string $packageManager,
        array $projectAnswers,
        array $themingAnswers,
        string $projectPath,
        InputInterface $input,
        OutputInterface $output,
    ): bool {
        $jsPath = $projectType === 'symfony' ? (
            $input->getOption('js-path') ?? 'assets/js'
        ) : ($input->getOption('js-path') ?? 'resources/js');
        $cssPath = $projectType === 'symfony' ? ($input->getOption('css-path') ?? 'assets/styles') : ($input->getOption('css-path') ?? 'resources/css');
        $folders['css'] = text('Where do you want to place your main CSS files', $cssPath, $cssPath);
        $folders['js']  = text('Where do you want to place your JS files', $jsPath, $jsPath);
        $folders['framework'] = $projectType;
        $answers = array_merge($projectAnswers, $folders, $themingAnswers);

        $plan = [];

        // Laravel-specific
        if ($projectType === 'laravel') {
            if (!empty($answers['livewire'])) {
                $plan[] = 'livewire';
            } elseif (!empty($answers['alpine'])) {
                $plan[] = 'alpine';
            }
        }

        // Symfony-specific
        if ($projectType === 'symfony' && !empty($answers['stimulus'])) {
            $plan[] = 'stimulus';
        }
        $cssFramework = $answers['cssFramework'] ?? null;

        // CSS Framework
        if ($cssFramework === 'unocss') {
            $plan[] = 'unocss';
        } elseif ($cssFramework === 'tailwindcss') {
            $plan[] = 'tailwindcss';
        }


        // Icon Library
        if ($answers['iconLibrary'] != null) {
            $plan[] = 'iconLibrary';
        }

        // Config + base files
        spin(fn() => [$this->createConfigFiles($answers), $this->generateBaseFiles($projectType, $answers)], "Setting up config files...");




        spin(fn() => PackageInstaller::node($packageManager)->install(''), "Installing dependencies");
        // Installers (strategy-based)
        $installers = [
            'livewire'   => new LivewireInstaller($answers),
            'alpine'     => new AlpineInstaller(),
            'stimulus'   => new StimulusInstaller(),
            'unocss'     => new UnoCSSInstaller(),
            'tailwindcss' => new TailwindInstaller(),
            'iconLibrary' => new IconLibraryInstaller($answers),
        ];

        $icon = $answers['iconLibrary'] ?? '';

        foreach ($plan as $key) {
            spin(fn() => $this->runInstaller($installers[$key], $packageManager, $projectPath, $answers, $key, $icon), "Installing {$key}...");
        }
        $output->writeln("<fg=green>✓ Packages Installation Completed</>");
        $this->addInstallationCompleted($plan, $icon, $cssFramework);
        $this->showCompletionSummary($output);

        return true;
    }

    private function createConfigFiles(array $answers): void
    {
        ConfigWriter::createFlexiwindYaml($answers);
        $this->completedActions[] = "<fg=green>⇒ Created: flexiwind.yaml</>";
    }

    private static function getIconInstalled($cssFramework, $icon)
    {
        return $cssFramework === 'tailwindcss' ? '@iconify/tailwind4 and ' . $icon . ' Icons' : $icon . ' Icons';
    }

    private function addInstallationCompleted($plan, $icon, $cssFramework)
    {
        foreach ($plan as $key) {
            $installed = $key == 'iconLibrary' ? self::getIconInstalled($cssFramework, $icon) : $key;
            $this->completedActions[] = "<fg=green>✓ Installed: " . $installed . "</>";
        }
    }

    private function generateBaseFiles(string $projectType, array $answers): void
    {
        FileGenerator::generateBaseFiles($projectType, $answers);

        // Track created files based on project type
        if ($projectType === 'laravel') {
            $this->completedActions[] = "<fg=green>⇒ Created: app/Flexiwind/UiHelper.php</>";
            $this->completedActions[] = "<fg=green>⇒ Created: app/Flexiwind/ButtonHelper.php</>";
            $this->completedActions[] = "<fg=green>⇒ Created: resources/views/components/layouts/base.blade.php</>";
            $this->completedActions[] = "<fg=green>⇒ Created: {$answers['css']}/app.css</>";
            $this->completedActions[] = "<fg=yellow>⇒ TODO: Don't forget to add 'resources/js/flexilla.js' in your vite config</>";
        } else {
            $this->completedActions[] = "<fg=green>⇒ Created: {$answers['css']}/styles.css</>";
        }

        $this->completedActions[] = "<fg=green>⇒ Created: {$answers['js']}/flexilla.js</>";
        if ($answers['cssFramework'] === 'tailwindcss') {
            $this->completedActions[] = "<fg=green>⇒ Created: {$answers['css']}/flexiwind.css</>";
            $this->completedActions[] = "<fg=green>⇒ Created: {$answers['css']}/button-styles.css</>";
            $this->completedActions[] = "<fg=green>⇒ Created: {$answers['css']}/ui-utilities.css</>";
        } else {
            $this->completedActions[] = "<fg=green>⇒ Created: {$answers['css']}/theme.css</>";
            $this->completedActions[] = "<fg=green>⇒ Created: vite.config.js</>";
            $this->completedActions[] = "<fg=green>⇒ Created: postcss.config.mjs</>";
        }
    }

    private function runInstaller($installer, string $packageManager, string $projectPath, array $answers, string $type, $icon): void
    {
        $installer->install($packageManager, $projectPath, $answers);
    }

    private function showCompletionSummary(OutputInterface $output): void
    {
        $output->writeln('===================================');
        foreach ($this->completedActions as $action) {
            $output->writeln($action);
        }
        $output->writeln('===================================');
        $output->write('✓ Flexiwind Setup Completed');
    }
}
