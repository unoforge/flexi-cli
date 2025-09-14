<?php

namespace FlexiCli\Service;

class ProjectDetector
{
    public static function check_Composer(string $path): bool
    {
        return file_exists($path . '/composer.json');
    }
    public static function detect(): string
    {
        $path = getcwd();
        if (file_exists($path . '/artisan')) {
            return 'laravel';
        }
        if (file_exists($path . '/bin/console')) {
            return 'symfony';
        }
        return 'generic';
    }

    public static function hasPackageJson(string $path)
    {
        return file_exists($path, '/package.json');
    }

    public static function checkTailwindCSS(): bool
    {
        $path = getcwd();
        // Check if package.json exists
        $packageJsonPath = $path . DIRECTORY_SEPARATOR . 'package.json';
        if (!file_exists($packageJsonPath)) {
            return false;
        }

        // Read and parse package.json
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        if (!$packageJson) {
            return false;
        }

        // Check for tailwindcss in dependencies or devDependencies
        $dependencies = array_merge(
            $packageJson['dependencies'] ?? [],
            $packageJson['devDependencies'] ?? []
        );

        if (isset($dependencies['tailwindcss'])) {
            return true;
        }

        return false;
    }

    public static function checkUnoCSS(): bool
    {
        $path = getcwd();
        // Check if package.json exists
        $packageJsonPath = $path . DIRECTORY_SEPARATOR . 'package.json';
        if (!file_exists($packageJsonPath)) {
            return false;
        }

        // Read and parse package.json
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        if (!$packageJson) {
            return false;
        }

        // Check for unocss in dependencies or devDependencies
        $dependencies = array_merge(
            $packageJson['dependencies'] ?? [],
            $packageJson['devDependencies'] ?? []
        );

        if (isset($dependencies['unocss']) || isset($dependencies['@unocss/cli'])) {
            return true;
        }

        // Also check for common UnoCSS config files
        $configFiles = [
            'uno.config.js',
            'uno.config.ts',
        ];

        foreach ($configFiles as $configFile) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $configFile)) {
                return true;
            }
        }

        return false;
    }



    public static function withBun(): bool
    {
        $path = getcwd();
        // Check for bun.lockb (Bun's binary lock file)
        if (file_exists($path . DIRECTORY_SEPARATOR . 'bun.lockb')) {
            return true;
        }

        // Check for bunfig.toml (Bun's configuration file)
        if (file_exists($path . DIRECTORY_SEPARATOR . 'bunfig.toml')) {
            return true;
        }

        // Check if package.json exists and has bun-specific scripts or configurations
        $packageJsonPath = $path . DIRECTORY_SEPARATOR . 'package.json';
        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if ($packageJson) {
                $scripts = $packageJson['scripts'] ?? [];
                foreach ($scripts as $script) {
                    if (strpos($script, 'bun ') === 0 || strpos($script, 'bun run') !== false) {
                        return true;
                    }
                }

                if (isset($packageJson['packageManager']) && strpos($packageJson['packageManager'], 'bun@') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getNodePackageManager()
    {
        $path = getcwd();
        $lockFiles = [
            'pnpm' => 'pnpm-lock.yaml',
            'yarn' => 'yarn.lock',
            'npm' => 'package-lock.json'
        ];

        foreach ($lockFiles as $manager => $lockFile) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $lockFile)) {
                return $manager;
            }
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . 'package.json')) {
            return 'npm';
        }

        return null;
    }
}
