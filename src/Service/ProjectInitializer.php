<?php

namespace FlexiCli\Service;

use FlexiCli\Service\ProjectDetector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\{select};

class ProjectInitializer
{
    public function initialize(InputInterface $input, OutputInterface $output): array
    {
        $projectAnswers = [];
        $initProjectFromCli = false;


        if ($input->getOption('new-laravel')) {
            $projectAnswers = (new ProjectCreator())->createLaravel($output);
            $initProjectFromCli = true;
        } elseif ($input->getOption('new-symfony')) {
            $projectAnswers = (new ProjectCreator())->createSymfony($output);
            $initProjectFromCli = true;
        }

        // Only check for existing projects if we haven't already created a new one
        if (!$initProjectFromCli) {
            if (!ProjectDetector::check_Composer(getcwd())) {
                $output->writeln('<fg=red>✘ No composer.json found.</>');

                $framework = select(
                    label: 'What framework are you using?',
                    options: ['laravel', 'symfony'],
                    default: 'laravel',
                );
                $projectAnswers = match ($framework) {
                    'laravel' => (new ProjectCreator())->createLaravel($output),
                    'symfony' => (new ProjectCreator())->createSymfony($output),
                    default   => (new ProjectCreator())->createLaravel($output),
                };


                $initProjectFromCli = true;
            } else {
                // Handle existing projects - ask about framework-specific features
                $detectedFramework = ProjectDetector::detect();
                if ($detectedFramework === 'laravel') {
                    $projectAnswers = $this->handleExistingLaravel();
                } elseif ($detectedFramework === 'symfony') {
                    $projectAnswers = $this->handleExistingSymfony();
                } else {
                    // Generic project
                    $projectAnswers = [];
                }
            }
        }

        return [$projectAnswers, $initProjectFromCli];
    }

    private function handleExistingLaravel(): array
    {
        $projectCreator = new ProjectCreator();
        // Ask about Livewire
        $livewire = $projectCreator->askLivewire();

        $alpine = false;

        if (!$livewire) {
            $alpine = $projectCreator->askAlpine();
        }

        return compact('livewire', 'alpine') + ['fromStarter' => false];
    }

    private function handleExistingSymfony(): array
    {
        $projectCreator = new ProjectCreator();
        $stimulus = $projectCreator->askStimulus();

        return compact('stimulus') + ['fromStarter' => false];
    }
}
