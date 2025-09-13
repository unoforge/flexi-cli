<?php

namespace Flexiwind\Installer;

use Flexiwind\Core\ConfigWriter;
use Flexiwind\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class TailwindInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager, $dir)->isInstalled('tailwindcss')) {
            PackageInstaller::node($packageManager, $dir)->install('tailwindcss @tailwindcss/vite');
            ConfigWriter::updateTailwindViteConfig();
        } elseif (!PackageInstaller::node($packageManager, $dir)->isInstalled('@tailwindcss/vite')) {
            PackageInstaller::node($packageManager, $dir)->install('@tailwindcss/vite');
            ConfigWriter::updateTailwindViteConfig();
        } else {
            note('TailwindCSS is already installed.');
        }
    }
}
