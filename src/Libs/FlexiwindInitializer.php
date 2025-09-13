<?php

namespace Flexiwind\Libs;

use Flexiwind\Core\{ConfigWriter, FileGenerator};
use Flexiwind\Installer\PackageInstaller;
use Flexiwind\Installer\{LivewireInstaller, AlpineInstaller, StimulusInstaller, UnoCSSInstaller, TailwindInstaller};
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

        // CSS Framework
        if (($answers['cssFramework'] ?? null) === 'unocss') {
            $plan[] = 'unocss';
        } elseif (($answers['cssFramework'] ?? null) === 'tailwindcss') {
            $plan[] = 'tailwindcss';
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
        ];

        foreach ($plan as $key) {
            spin(fn() => $this->runInstaller($installers[$key], $packageManager, $projectPath, $answers, $key), "Installing {$key}...");
        }
        $output->writeln("<fg=green>✓ Packages Installation Completed</>");
        $this->showCompletionSummary($output);

        return true;
    }

    private function createConfigFiles(array $answers): void
    {
        ConfigWriter::createFlexiwindYaml($answers);
        $this->completedActions[] = "Created: flexiwind.yaml";
    }

    private function generateBaseFiles(string $projectType, array $answers): void
    {
        FileGenerator::generateBaseFiles($projectType, $answers);

        // Track created files based on project type
        if ($projectType === 'laravel') {
            $this->completedActions[] = "Created: app/Flexiwind/UiHelper.php";
            $this->completedActions[] = "Created: app/Flexiwind/ButtonHelper.php";
            $this->completedActions[] = "Created: resources/views/layouts/base.blade.php";
            $this->completedActions[] = "Created: {$answers['css']}/app.css";
        } else {
            $this->completedActions[] = "Created: {$answers['css']}/styles.css";
        }

        $this->completedActions[] = "Created: {$answers['js']}/flexilla.js";
        $this->completedActions[] = "Created: {$answers['css']}/base-colors.css";
        $this->completedActions[] = "Created: {$answers['css']}/flexiwind.css";
        $this->completedActions[] = "Created: {$answers['css']}/button-styles.css";
        $this->completedActions[] = "Created: {$answers['css']}/ui-utilities.css";
    }

    private function runInstaller($installer, string $packageManager, string $projectPath, array $answers, string $type): void
    {
        $installer->install($packageManager, $projectPath, $answers);
        $this->completedActions[] = "<fg=green>✓ Installed: {$type}</>";
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
