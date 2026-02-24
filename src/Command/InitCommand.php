<?php

namespace FlexiCli\Command;


use FlexiCli\Service\{ProjectCreator, ProjectInitializer, ThemingInitializer, ProjectDetector};
use FlexiCli\Libs\FlexiwindInitializer;
use FlexiCli\Core\Constants;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\{ info};

class InitCommand extends Command
{
    private ?OutputInterface $output = null;

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
            ->addOption('no-flexiwind', null, InputOption::VALUE_NONE, 'Initialize without Flexiwind UI')
            ->addOption('js-path', null, InputOption::VALUE_OPTIONAL, 'Path to the JS files', 'resources/js')
            ->addOption('css-path', null, InputOption::VALUE_OPTIONAL, 'Path to the CSS files', 'resources/css');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $isFlexiwind = true;

        $this->displayName();

        if($this->checkIsInitialized()){
            $output->writeln('<fg=yellow>⚠️  Flexiwind has already been initialized in this project. No further action is needed.</>');
            return Command::SUCCESS;
        }

        if ($input->getOption('no-flexiwind')) {
            $isFlexiwind = false;
        }

        [$projectAnswers, $initProjectFromCli] = (new ProjectInitializer())->initialize($input, $output);

        // For new projects, we need valid project answers
        if ($initProjectFromCli && empty($projectAnswers)) {
            return Command::FAILURE;
        }

        if (!empty($projectAnswers['fromStarter'])) {
            info('Starter projects are not yet implemented.');
            return Command::SUCCESS;
        }

        $projectPath = $initProjectFromCli
            ? $projectAnswers['projectPath'] ?? getcwd() . '/' . ($projectAnswers['name'] ?? 'my-app')
            : getcwd();



        $projectType     = ProjectDetector::detect();
        $packageManager  = ProjectDetector::getNodePackageManager();
        $themingAnswers  = $this->themingInitializer->askTheming($isFlexiwind);

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

    private function checkIsInitialized(): bool
    {
        $configFile = getcwd() . '/' . Constants::CONFIG_FILE;
        
        if (!file_exists($configFile)) {
            return false;
        }
        
        try {
            $config = Yaml::parseFile($configFile);
            $requiredKeys = ['framework', 'defaultSource', 'registries'];
            foreach ($requiredKeys as $key) {
                if (!isset($config[$key])) {
                    return false;
                }
            }
            

            if (!is_array($config['registries'])) {
                return false;
            }
            if (empty($config['registries'])) {
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displayName(): void
    {
        $output = <<<'ASCII'
<fg=red>
  ███████╗██╗     ███████╗██╗  ██╗██╗      ██████╗██╗     ██╗
  ██╔════╝██║     ██╔════╝╚██╗██╔╝██║     ██╔════╝██║     ██║
  █████╗  ██║     █████╗   ╚███╔╝ ██║     ██║     ██║     ██║
  ██╔══╝  ██║     ██╔══╝   ██╔██╗ ██║     ██║     ██║     ██║
  ██║     ███████╗███████╗██╔╝ ██╗██║     ╚██████╗███████╗██║
  ╚═╝     ╚══════╝╚══════╝╚═╝  ╚═╝╚═╝      ╚═════╝╚══════╝╚═╝
  Modern PHP Web Application Scaffolding Tool
</>
ASCII;

        $this->output->writeln($output);
    }
}
