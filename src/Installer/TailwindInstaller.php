<?php

namespace FlexiCli\Installer;

use FlexiCli\Core\ConfigWriter;
use FlexiCli\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class TailwindInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager, $dir)->isInstalled('tailwindcss')) {
            PackageInstaller::node($packageManager, $dir)->install('tailwindcss @tailwindcss/vite');
        } elseif (!PackageInstaller::node($packageManager, $dir)->isInstalled('@tailwindcss/vite')) {
            PackageInstaller::node($packageManager, $dir)->install('@tailwindcss/vite');
        } else {
            note('TailwindCSS is already installed.');
        }
    }
}
