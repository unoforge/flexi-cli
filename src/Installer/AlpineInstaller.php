<?php

namespace Flexiwind\Installer;

use Flexiwind\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class AlpineInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager)->isInstalled('alpinejs')) {
            PackageInstaller::node($packageManager)->install('alpinejs');
        } else {
            note('AlpineJS is already installed.');
        }
    }
}
