<?php

namespace FlexiCli\Installer;

use FlexiCli\Core\ConfigWriter;
use FlexiCli\Core\Constants;
use FlexiCli\Installer\PackageInstaller;
use function Laravel\Prompts\note;

class IconLibraryInstaller implements InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void
    {
        if (!PackageInstaller::node($packageManager, $dir)->isInstalled('@iconify/tailwind4')) {
            PackageInstaller::node($packageManager, $dir)->install('@iconify/tailwind4', true);
        }
        try {
            $icon = Constants::UI_ICONS[$options['iconLibrary']];
            PackageInstaller::node($packageManager, $dir)->install("@iconify-json/".$icon, true);
        } catch (\Exception $e) {
            note('Icon library is already installed.');
        }
    }
}
