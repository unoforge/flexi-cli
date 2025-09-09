<?php

namespace Flexiwind\Command;

use Flexiwind\Core\{ConfigWriter, FileGenerator};
use Flexiwind\Installer\PackageInstaller;
use Flexiwind\Service\{ProjectCreator, ProjectInitializer, ThemingInitializer, ProjectDetector};
use Flexiwind\Installer\{LivewireInstaller, AlpineInstaller, StimulusInstaller, UnoCSSInstaller, TailwindInstaller};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\{note, info, spin, text};

class InitCommand extends Command
{

    public function __construct(
        private ProjectCreator $projectCreator = new ProjectCreator(),
        private ThemingInitializer $themingInitializer = new ThemingInitializer(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize Flexiwind in your project')
            ->addOption('new-laravel', 'nl', InputOption::VALUE_NONE, 'Create a new Laravel project')
            ->addOption('new-symfony', 'ns', InputOption::VALUE_NONE, 'Create a new Symfony project')
            ->addOption('tailwind', null, InputOption::VALUE_NONE, 'Use tailwindcss')
            ->addOption('uno', null, InputOption::VALUE_NONE, 'Use UnoCSS');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        note('⚡ Flexiwind Initializer');

        [$projectAnswers, $initProjectFromCli] = (new ProjectInitializer())->initialize($input);

        // For new projects, we need valid project answers
        if ($initProjectFromCli && empty($projectAnswers)) {
            return Command::FAILURE;
        }

        // to be complated 
        if ($projectAnswers['fromStarter']) {
            info('Starter projects are not yet implemented.');
            return Command::SUCCESS;
        }

        $projectPath = $initProjectFromCli
            ? $projectAnswers['projectPath'] ?? getcwd() . '/' . ($projectAnswers['name'] ?? 'my-app')
            : getcwd();



        $projectType     = ProjectDetector::detect();
        $packageManager  = ProjectDetector::getNodePackageManager();
        $themingAnswers  = $this->themingInitializer->askTheming(
            $input->getOption('tailwind') ? 'tailwindcss' : ($input->getOption('uno') ? 'unocss' : '')
        );

        $defaultCssPath = $projectType === 'symfony' ? 'assets/styles' : 'resources/css';
        $defaultJsPath = $projectType === 'symfony' ? 'assets/js' : 'resources/js';
        $folders['css'] = text('Where do you want to place your main CSS files', $defaultCssPath, $defaultCssPath);
        $folders['js']  = text('Where do you want to place your JS files', $defaultJsPath, $defaultJsPath);
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
        spin(fn() => [ConfigWriter::createFlexiwindYaml($answers), ConfigWriter::createKeysYaml()], "Creating config files...");
        spin(fn() => FileGenerator::generateBaseFiles($projectType, $answers), "Creating base files...");

        // Installers (strategy-based)
        $installers = [
            'livewire'   => new LivewireInstaller($answers),
            'alpine'     => new AlpineInstaller(),
            'stimulus'   => new StimulusInstaller(),
            'unocss'     => new UnoCSSInstaller(),
            'tailwindcss' => new TailwindInstaller(),
        ];

        foreach ($plan as $key) {
            spin(fn() => $installers[$key]->install($packageManager, $projectPath, $answers), "Installing {$key}...");
        }

        spin(fn() => PackageInstaller::node($packageManager)->install(''), "Installing dependencies");
        info('✔ Flexiwind initialization complete!');

        return Command::SUCCESS;
    }
}
