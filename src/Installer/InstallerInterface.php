<?php

namespace Flexiwind\Installer;

interface InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void;
}
