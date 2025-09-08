<?php

namespace Flexiwind\Service;

use Flexiwind\Service\ProjectDetector;
use Symfony\Component\Console\Input\InputInterface;
use function Laravel\Prompts\{warning, select};

class ProjectInitializer
{
    public function initialize(InputInterface $input): array
    {
        $projectAnswers = [];
        $initProjectFromCli = false;

        if ($input->getOption('new-laravel')) {
            $projectAnswers = (new ProjectCreator())->createLaravel();
            $initProjectFromCli = true;
        } elseif ($input->getOption('new-symfony')) {
            $projectAnswers = (new ProjectCreator())->createSymfony();
            $initProjectFromCli = true;
        }

        if (!ProjectDetector::check_Composer(getcwd())) {
            warning('❌ No composer.json found.');

            $framework = select(
                label: 'What framework are you using?',
                options: ['laravel', 'symfony'],
                default: 'laravel',
            );
            $projectAnswers = match ($framework) {
                'laravel' => (new ProjectCreator())->createLaravel(),
                'symfony' => (new ProjectCreator())->createSymfony(),
                default   => (new ProjectCreator())->createLaravel(),
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

        return [$projectAnswers, $initProjectFromCli];
    }

    private function handleExistingLaravel(): array
    {
        $projectCreator = new ProjectCreator();

        // Ask about Livewire
        $livewire = $projectCreator->askLivewire();
        $volt = false;
        $alpine = false;

        if ($livewire) {
            $volt = $projectCreator->askLivewireVolt();
        } else {
            $alpine = $projectCreator->askAlpine();
        }

        return compact('livewire', 'alpine', 'volt');
    }

    private function handleExistingSymfony(): array
    {
        $projectCreator = new ProjectCreator();
        $stimulus = $projectCreator->askStimulus();

        return compact('stimulus');
    }
}
