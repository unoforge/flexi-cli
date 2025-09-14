<?php

namespace FlexiCli\Command;

use FlexiCli\Installer\PackageInstaller;
use FlexiCli\Utils\FileUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\{note, info, warning, spin, confirm};

class CleanFluxCommand extends Command
{
    private string $projectRoot;
    private ?OutputInterface $output = null;

    public function __construct()
    {
        parent::__construct();
        $this->projectRoot = getcwd();
    }

    protected function configure(): void
    {
        $this
            ->setName('clean:flux')
            ->setDescription('Remove Livewire Flux package and clean up related files')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $this->output = $output;

        if (!$force && !confirm('This will remove Livewire Flux and all related files. Continue?', false)) {
            info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->removeFluxPackage();
        $this->cleanFluxFiles();

        $output->writeln('<fg=green>✔ Livewire Flux cleanup complete!</>');
        if ($force) {
            
        } elseif (confirm('Now the CLI will generate back the Starters without Flux, Do you want it?', false)) {

        }

        return Command::SUCCESS;
    }

    private function removeFluxPackage(): void
    {
        $composer = PackageInstaller::composer($this->projectRoot);
        if ($composer->isInstalled('livewire/flux')) {
            spin(
                message: 'Removing Livewire Flux package...',
                callback: fn() => $composer->remove('livewire/flux')
            );
            $this->output->writeln('<fg=green>✔ Livewire Flux package removed</>');
        } else {
            $this->output->writeln('<fg=blue>Livewire Flux package not found</>');
        }
    }

    private function cleanFluxFiles(): void
    {
        $fluxPaths = [
            'resources/views/flux',
            'resources/views/components/layouts/auth',
            'resources/views/components/layouts/app',
            'resources/views/components/layouts/app.blade.php',
            'resources/views/components/layouts/auth.blade.php',
            'resources/views/dashboard.blade.php',
            'resources/views/partials',
            'resources/views/livewire/settings',
            'resources/views/livewire/auth',
            'resources/views/components/settings',
            'resources/views/components/action-message.blade.php',
            'resources/views/components/app-logo-icon.blade.php',
            'resources/views/components/app-logo.blade.php',
            'resources/views/components/auth-header.blade.php',
            'resources/views/components/auth-session-status.blade.php',
            'resources/views/components/placeholder-pattern.blade.php'

        ];

        foreach ($fluxPaths as $path) {
            $fullPath = $this->projectRoot . '/' . $path;
            if (file_exists($fullPath)) {
                spin(
                    message: "Removing {$path}...",
                    callback: fn() => FileUtils::deleteDirectory($fullPath)
                );
            }
        }
    }
}
