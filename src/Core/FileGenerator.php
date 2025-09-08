<?php

namespace Flexiwind\Core;

use Flexiwind\Core\StubStorage;

class FileGenerator
{
    public static function generateBaseFiles(string $projectType, array $answers): void
    {
        if ($projectType === 'laravel') {
            self::createLaravelFiles($answers['js'], $answers['css'], $answers['themingMode']);
        } else {
            self::createSymfonyFiles($answers['js'], $answers['css'], $answers['themingMode']);
        }
    }

    public static function createFlexiwindFiles($jsFolder, $cssFolder, $themingMode, $mainCssFileName)
    {
        // Create directories if they don't exist
        if (!is_dir($jsFolder)) {
            mkdir($jsFolder, 0755, true);
        }
        if (!is_dir($cssFolder)) {
            mkdir($cssFolder, 0755, true);
        }
        $themingFolder = strtolower($themingMode) == 'both' ? '' : strtolower($themingMode) . '.';


        file_put_contents(
            $cssFolder . "/$mainCssFileName.css",
            StubStorage::get($themingFolder . 'css.base')
        );
        file_put_contents(
            $jsFolder . '/flexilla.js',
            StubStorage::get('js.flexilla')
        );
        file_put_contents(
            $cssFolder . '/flexiwind.css',
            StubStorage::get('css.flexiwind')
        );
        file_put_contents(
            $cssFolder . '/button-styles.css',
            StubStorage::get($themingFolder . 'css.buttons')
        );
        file_put_contents(
            $cssFolder . '/ui-utilities.css',
            StubStorage::get($themingFolder . 'css.utilities')
        );
    }

    private static function createLaravelFiles($jsFolder, $cssFolder, $themingMode): void
    {
        if (!is_dir('app/Flexiwind')) {
            mkdir('app/Flexiwind', 0755, true);
        }

        file_put_contents(
            'app/Flexiwind/UiHelper.php',
            StubStorage::get('laravel.ui_helper')
        );

        file_put_contents(
            'app/Flexiwind/ButtonHelper.php',
            StubStorage::get('laravel.button_helper')
        );

        self::createFlexiwindFiles($jsFolder, $cssFolder, $themingMode, 'app');
        self::createLaravelBaseLayout();
    }


    private static function createSymfonyFiles($jsFolder, $cssFolder, $themingMode): void
    {
        // to be improved : init stimulus, create required files...

        self::createFlexiwindFiles($jsFolder, $cssFolder, $themingMode, 'styles');
        self::createSymfonyBaseLayout();
    }

    public static function createLaravelBaseLayout() {}

    public static function createSymfonyBaseLayout() {}
}
