<?php

namespace FlexiCli\Installer;

interface InstallerInterface
{
    public function install(string $packageManager, string $dir, array $options = []): void;
}
