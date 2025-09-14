<?php

namespace FlexiCli\Service;

use FlexiCli\Installer\PackageInstaller;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\{text, spin, confirm, select, warning};

// TODO: Improve this later
class ProjectCreator
{
    public function createLaravel(OutputInterface $output): array
    {
        $app = $this->runComposerInit('Laravel', $output, true);
        $projectPath = $app['projectPath'];
        $fromStarter = $app['fromStarter'];
        $livewire = $alpine = $volt = false;

        if (!$fromStarter) {
            $livewire = $this->askLivewire();
            if ($livewire) $volt = $this->askLivewireVolt();
            if (!$livewire) $alpine = $this->askAlpine();
        }
        return compact('livewire', 'alpine', 'volt', 'projectPath', 'fromStarter');
    }

    public function createSymfony(OutputInterface $output): array
    {
        $app = $this->runComposerInit('Symfony', $output);
        $fromStarter = $app['fromStarter'];
        $projectPath = $app['projectPath'];

        $stimilus = !$fromStarter ? $this->askStimulus() : false;;
        return compact('stimilus', 'projectPath', 'fromStarter');
    }

    private function runComposerInit(string $label, $output, bool $isLaravel = false)
    {

        $output->writeln("<fg=red>======= Setup a new $label project. =======</>");
        $name = text(
            label: 'What is the name of your project?',
            default: 'my-app'
        );

        $useStarter = confirm('Do you want to use a starter project?', false);

        if ($useStarter) {
            $isLaravel ? $this->askLaravelStarters() : $this->askSymfonyStarters();


            // temporary handle folder creation
            if (!is_dir($name)) {
                mkdir($name);
            }
        } else {
            $createCommand = $isLaravel ? "laravel new $name --no-interaction" : "composer create-project symfony/skeleton $name";
            spin(
                callback: fn() => exec($createCommand, $output, $returnCode),
                message: "Creating a new empty $label project"
            );
            $output->writeln("<bg=green;fg=white> CREATED </> <fg=green>{$label} project created</>");
        }

        // Check if directory was created successfully before changing to it
        if (!is_dir($name)) {
            throw new \Exception("Failed to create project directory: $name");
        }

        chdir($name);
        return [
            'projectPath' => $name,
            'fromStarter' => $useStarter
        ];
    }

    public function askLivewire()
    {
        if (PackageInstaller::composer()->isInstalled('livewire/livewire')) {
            return true;
        }

        return confirm('Do you want to install livewire?');
    }
    public function askLivewireVolt()
    {
        if (PackageInstaller::composer()->isInstalled('livewire/volt')) {
            return true;
        }
        return confirm('Do you want to use Livewire Volt?');
    }
    public function askAlpine()
    {
        return confirm('Do you want to install AlpineJS?');
    }


    public function askStimulus()
    {
        return confirm('Do you want to install Stimulus?');
    }

    private function askLaravelStarters()
    {
        $starters = select(
            label: 'What starter do you want to use?',
            options: [
                'uno_livewire' => "Livewire + UnoCSS + Volt",
                'uno_blade' => "Blade + UnoCSS",
                'uno_blade_alpineJS' => "UnoCSS + AlpineJS + Blade",
                'tailwind_livewire' => "Livewire + TailwindCSS + Volt",
                'tailwind_blade' => "Blade + TailwindCSS",
                'tailwind_blade_alpineJS' => "TailwindCSS + AlpineJS + Blade"
            ],
            default: 'tailwind_livewire',
        );
        warning("OOps, this feature is not yet implemented.");
        // note("Creating project with starter $starters");

        // handle folder creation

        // clone project

        // think about adding array data for answers 
    }

    private function askSymfonyStarters()
    {
        $starters = select(
            label: 'What starter do you want to use?',
            options: [
                'stimulus_ux' => "Symfony UX + Stimulus",
                'stimulus_ux_tailwind' => "Symfony UX + Stimulus + TailwindCSS",
                'twig_tailwind' => "Twig + TailwindCSS",
                'twig_alpine' => "Twig + AlpineJS"
            ],
            default: 'stimulus_ux_tailwind',
        );

        warning("OOps, this feature is not yet implemented.");
    }
}
