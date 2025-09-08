<?php

namespace Flexiwind\Installer;

use RuntimeException;

class ComposerInstaller
{
    public function __construct(private ?string $workingDir = null)
    {
        $this->workingDir = $workingDir ?? getcwd();
    }

    private function ensureComposerJson(): void
    {
        $file = $this->workingDir . '/composer.json';
        if (!file_exists($file)) {
            throw new RuntimeException("composer.json not found in {$this->workingDir}");
        }
    }

    private function runCommand(string $command): bool
    {
        $output = [];
        $returnCode = 0;
        exec("cd " . escapeshellarg($this->workingDir) . " && {$command} 2>&1", $output, $returnCode);
        return $returnCode === 0;
    }

    public function install(string $packageName, bool $isDevDep = false): bool
    {
        $this->ensureComposerJson();
        $flag = $isDevDep ? "--dev" : "";
        // Important: put flag AFTER the package name
        return $this->runCommand("composer require " . escapeshellarg($packageName) . " {$flag}");
    }

    public function remove(string $packageName): bool
    {
        $this->ensureComposerJson();
        return $this->runCommand("composer remove " . escapeshellarg($packageName));
    }

    public function isInstalled(string $packageName): bool
    {
        $this->ensureComposerJson();
        $composerJson = json_decode(file_get_contents($this->workingDir . '/composer.json'), true) ?: [];
        $dependencies = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );
        return isset($dependencies[$packageName]);
    }

    public function getInstalledPackages(): array
    {
        $this->ensureComposerJson();
        $composerJson = json_decode(file_get_contents($this->workingDir . '/composer.json'), true) ?: [];
        return array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );
    }

    public function getVersion(string $packageName): ?string
    {
        $packages = $this->getInstalledPackages();
        return $packages[$packageName] ?? null;
    }

    public function isAvailable(): bool
    {
        exec("composer --version 2>&1", $out, $code);
        return $code === 0;
    }
}
