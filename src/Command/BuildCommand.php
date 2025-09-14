<?php

namespace FlexiCli\Command;

use FlexiCli\Core\{RegistryBuilder, Constants};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\{info, error};

class BuildCommand extends Command
{
    protected static $defaultName = 'build';

    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Build registries from flexiwind.schema.json')
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output directory name (relative to current directory)',
                Constants::DEFAULT_BUILD_OUTPUT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $builder = new RegistryBuilder();
        $outputDir = $input->getOption('output');
        $cleanOutputDir = trim($outputDir, '/\\');
        $fullOutputPath = getcwd() . DIRECTORY_SEPARATOR . $cleanOutputDir;

        try {
            $builder->build('registry.json', $fullOutputPath);
            info("Registries built successfully in: {$cleanOutputDir}");
        } catch (\Exception $e) {
            error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
