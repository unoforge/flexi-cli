<?php

namespace Flexiwind\Installer;

use RuntimeException;
use Symfony\Component\Process\Process;

class NodePackageInstaller
{
    private array $validManagers = ['npm', 'yarn', 'pnpm', 'bun'];

    public function __construct(
        private string $packageManager,
        private string $workingDir = ''
    ) {
        $this->workingDir = getcwd();

        if (!in_array($this->packageManager, $this->validManagers)) {
            throw new RuntimeException("Invalid package manager: {$this->packageManager}");
        }
    }

    private function ensurePackageJson(): void
    {
        if (!file_exists($this->workingDir . '/package.json')) {
            throw new RuntimeException("package.json not found in {$this->workingDir}");
        }
    }

    private function runCommand(string $command): bool
    {
        $process = Process::fromShellCommandline($command, $this->workingDir);
        $process->run();
        return $process->isSuccessful();
    }

    public function install(string $packageName, bool $isDevDep = false): bool
    {
        $this->ensurePackageJson();
        return $this->runCommand($this->buildInstallCommand($packageName, $isDevDep));
    }

    public function remove(string $packageName): bool
    {
        $this->ensurePackageJson();
        return $this->runCommand($this->buildRemoveCommand($packageName));
    }

    public function isInstalled(string $packageName): bool
    {
        $this->ensurePackageJson();
        $process = Process::fromShellCommandline("{$this->packageManager} list --depth=0 --json", $this->workingDir);
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        $data = json_decode($process->getOutput(), true);
        return isset($data['dependencies'][$packageName]);
    }

    public function getInstalledPackages(): array
    {
        $this->ensurePackageJson();
        $process = Process::fromShellCommandline("{$this->packageManager} list --depth=0 --json", $this->workingDir);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $data = json_decode($process->getOutput(), true);
        return $data['dependencies'] ?? [];
    }

    public function getVersion(string $packageName): ?string
    {
        $packages = $this->getInstalledPackages();
        return $packages[$packageName]['version'] ?? null;
    }

    public function isAvailable(): bool
    {
        $process = Process::fromShellCommandline("{$this->packageManager} --version");
        $process->run();
        return $process->isSuccessful();
    }

    public function buildInstallCommand(string $packageName, bool $isDevDep): string
    {
        return match ($this->packageManager) {
            'npm'  => "npm install " . $packageName . ($isDevDep ? " -D" : ""),
            'yarn' => "yarn add " . $packageName . ($isDevDep ? " --dev" : ""),
            'pnpm' => "pnpm add " . $packageName . ($isDevDep ? " --save-dev" : " --save"),
            'bun'  => "bun add " . $packageName . ($isDevDep ? " --development" : ""),
        };
    }

    private function buildRemoveCommand(string $packageName): string
    {
        return match ($this->packageManager) {
            'npm'  => "npm uninstall " . $packageName,
            'yarn' => "yarn remove " . $packageName,
            'pnpm' => "pnpm remove " . $packageName,
            'bun'  => "bun remove " . $packageName,
        };
    }
}
