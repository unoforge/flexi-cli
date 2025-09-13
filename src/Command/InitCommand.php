<?php

namespace Flexiwind\Command;


use Flexiwind\Service\{ProjectCreator, ProjectInitializer, ThemingInitializer, ProjectDetector};
use Flexiwind\Libs\FlexiwindInitializer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\{note, info};

class InitCommand extends Command
{

    public function __construct(
        private ProjectCreator $projectCreator = new ProjectCreator(),
        private ThemingInitializer $themingInitializer = new ThemingInitializer(),
        private FlexiwindInitializer $flexiwindInitializer = new FlexiwindInitializer(),
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
            ->addOption('uno', null, InputOption::VALUE_NONE, 'Use UnoCSS')
            ->addOption('no-flexiwind', null, InputOption::VALUE_NONE, 'Initialize without Flexiwind UI')
            ->addOption('js-path', null, InputOption::VALUE_OPTIONAL, 'Path to the JS files', 'resources/js')
            ->addOption('css-path', null, InputOption::VALUE_OPTIONAL, 'Path to the CSS files', 'resources/css');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isFlexiwind = true;
        
        $output->writeln($this->displayName());

        if ($input->getOption('no-flexiwind')) {
            $isFlexiwind = false;
        }

        [$projectAnswers, $initProjectFromCli] = (new ProjectInitializer())->initialize($input, $output);

        // For new projects, we need valid project answers
        if ($initProjectFromCli && empty($projectAnswers)) {
            return Command::FAILURE;
        }

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
            $input->getOption('tailwind') ? 'tailwindcss' : ($input->getOption('uno') ? 'unocss' : ''),
            $isFlexiwind
        );

        if ($isFlexiwind == 'flexiwind') {
            $this->flexiwindInitializer->initialize(
                $projectType,
                $packageManager,
                $projectAnswers,
                $themingAnswers,
                $projectPath,
                $input,
                $output
            );
        } else {
            info('Initialization without Flexiwind is not yet implemented.');
        }

        return Command::SUCCESS;
    }

    private function displayName(): string
    {
        $output = <<<'ASCII'
<fg=red>
  ███████╗██╗     ███████╗██╗  ██╗██╗██╗    ██╗██╗███╗   ██╗██████╗ 
  ██╔════╝██║     ██╔════╝╚██╗██╔╝██║██║    ██║██║████╗  ██║██╔══██╗
  █████╗  ██║     █████╗   ╚███╔╝ ██║██║ █╗ ██║██║██╔██╗ ██║██║  ██║
  ██╔══╝  ██║     ██╔══╝   ██╔██╗ ██║██║███╗██║██║██║╚██╗██║██║  ██║
  ██║     ███████╗███████╗██╔╝ ██╗██║╚███╔███╔╝██║██║ ╚████║██████╔╝
  ╚═╝     ╚══════╝╚══════╝╚═╝  ╚═╝╚═╝ ╚══╝╚══╝ ╚═╝╚═╝  ╚═══╝╚═════╝ 
  Modern PHP Web Application Scaffolding Tool
</>
ASCII;
        
        return $output;
    }
}
