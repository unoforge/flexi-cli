<?php

namespace Flexiwind\Installer;
use Flexiwind\Installer\NodePackageInstaller;
use Flexiwind\Installer\ComposerInstaller;

class PackageInstaller
{
    public static function composer(?string $workingDir = null): ComposerInstaller
    {
        return new ComposerInstaller($workingDir);
    }

    public static function node(string $packageManager, ?string $workingDir = null): NodePackageInstaller
    {
        return new NodePackageInstaller($packageManager, getcwd());
    }
}
