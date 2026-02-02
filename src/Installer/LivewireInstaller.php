<?php

namespace FlexiCli\Installer;

use FlexiCli\Installer\PackageInstaller;

class LivewireInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (PackageInstaller::node($packageManager)->isInstalled('alpinejs')) {
            PackageInstaller::node($packageManager)->remove('alpinejs');
        }
        PackageInstaller::composer()->install('livewire/livewire');
    }
}
