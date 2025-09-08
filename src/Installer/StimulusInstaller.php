<?php

namespace Flexiwind\Installer;

use Flexiwind\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class StimulusInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager, $dir)->isInstalled('stimulus')) {
            note('Stimulus not found. Installing...');
            PackageInstaller::node($packageManager, $dir)->install('stimulus');
        } else {
            note('Stimulus is already installed.');
        }
    }
}
