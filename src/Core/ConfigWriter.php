<?php

namespace FlexiCli\Core;


use FlexiCli\Core\FileEditor;
use FlexiCli\Core\StubStorage;

class ConfigWriter
{
    public static function createFlexiwindYaml(array $answers): void
    {
        $yaml = "framework: {$answers['framework']}\n";
        if ($answers['framework'] === 'laravel') {
            $livewireValue = $answers['livewire'] ? 'true' : 'false';
            $alpineValue = $answers['alpine'] ? 'true' : 'false';
            $yaml .= "livewire: {$livewireValue}\n";
            $yaml .= "alpine: {$alpineValue}\n";
        }

        if ($answers['framework'] === 'symfony') {
            $yaml .= "stimulus: {$answers['stimilus']}\n";
        }

        $yaml .= "theme: {$answers['theme']}\n";
        $yaml .= "themeMode: {$answers['themingMode']}\n";
        $yaml .= "cssFramework: {$answers['cssFramework']}\n";
        $yaml .= "js_folder: {$answers['js']}\n";
        $yaml .= "css_folder: {$answers['css']}\n";
        
        // Add default registry configuration
        $yaml .= "defaultSource: " . Constants::DEFAULT_REGISTRY . "\n";
        $yaml .= "registries:\n";
        $yaml .= "  '" . Constants::FLEXIWIND_NAMESPACE . "': " . Constants::DEFAULT_REGISTRY . "\n";

        file_put_contents('flexiwind.yaml', $yaml);
    }



    public static function updateTailwindViteConfig(): void
    {
        $viteConfigPath = self::findViteConfigFile();

        if (!$viteConfigPath) {
            throw new \RuntimeException("Vite config file not found. Expected vite.config.js or vite.config.ts");
        }

        $newConfig = StubStorage::get('tailwind.vite');
        FileEditor::updateFileContent($viteConfigPath, $newConfig);
    }

    public static function updateUnoConfig(): void
    {
        $unoConfigPath = self::findUnoConfigFile();

        if (!$unoConfigPath) {
            $unoConfigPath = getcwd() . '/uno.config.js';
        }
        $newConfig = StubStorage::get('uno.config.js');
        FileEditor::updateFileContent($unoConfigPath, $newConfig);
    }

    public static function addPostCssConfigUno()
    {
        if (!file_exists(getcwd() . '/postcss.config.js')) {
            touch(getcwd() . '/postcss.config.js');
        }
        FileEditor::updateFileContent(getcwd() . '/postcss.config.js', StubStorage::get('uno.postcss'));
    }

    public static function updateUnoViteConfig(): void
    {
        $viteConfigPath = self::findViteConfigFile();

        if (!$viteConfigPath) {
            throw new \RuntimeException("Vite config file not found. Expected vite.config.js or vite.config.ts");
        }

        $newConfig = StubStorage::get('uno.vite');
        FileEditor::updateFileContent($viteConfigPath, $newConfig);
    }

    private static function findViteConfigFile(): ?string
    {
        $possibleFiles = [
            getcwd() . '/vite.config.js',
            getcwd() . '/vite.config.ts'
        ];

        foreach ($possibleFiles as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    private static function findUnoConfigFile(): ?string
    {
        $possibleFiles = [
            getcwd() . '/uno.config.js',
            getcwd() . '/uno.config.ts'
        ];

        foreach ($possibleFiles as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }
}
