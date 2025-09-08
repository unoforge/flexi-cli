<?php

namespace Flexiwind\Installer;

use Flexiwind\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class LivewireInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (PackageInstaller::node($packageManager)->isInstalled('alpinejs')) {
            note('AlpineJS found. Uninstalling...');
            PackageInstaller::node($packageManager)->remove('alpinejs');
        }

        note('Installing livewire package');
        PackageInstaller::composer()->install('livewire/livewire');
        if ($options['volt']) {
            note('Adding Livewire/Volt package');
            PackageInstaller::composer()->install('livewire/volt');
        }
    }
}
