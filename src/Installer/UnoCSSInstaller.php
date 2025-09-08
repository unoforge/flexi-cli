<?php

namespace Flexiwind\Installer;

use Flexiwind\Core\ConfigWriter;
use Flexiwind\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class UnoCSSInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager)->isInstalled('unocss')) {
            if (PackageInstaller::node($packageManager)->isInstalled('tailwindcss')) {
                note("Tailwindcss found, uninstalling");
                PackageInstaller::node($packageManager)->remove('tailwindcss @tailwindcss/vite');
            }
            note('UnoCSS not found. Installing...');
            PackageInstaller::node($packageManager)->install('unocss @unifydev/preset-ui @unifydev/flexilla', true);
            note('UnoCSS installed. Updating vite.config');
            ConfigWriter::updateUnoViteConfig();
            ConfigWriter::updateUnoConfig();
        } else {
            note('UnoCSS is already installed.');
        }
    }
}
