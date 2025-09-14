<?php

namespace FlexiCli\Installer;

use FlexiCli\Core\ConfigWriter;
use FlexiCli\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class UnoCSSInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager)->isInstalled('unocss')) {
            if (PackageInstaller::node($packageManager)->isInstalled('tailwindcss')) {
                PackageInstaller::node($packageManager)->remove('tailwindcss @tailwindcss/vite');
            }
            PackageInstaller::node($packageManager)->install('unocss @unifydev/preset-ui @unifydev/flexilla', true);
            ConfigWriter::updateUnoViteConfig();
            ConfigWriter::updateUnoConfig();
        } else {
            note('UnoCSS is already installed.');
        }
    }
}
