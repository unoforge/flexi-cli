<?php

namespace Flexiwind\Core;

use Flexiwind\Core\{StubStorage, Constants};

class FileGenerator
{
    public static function generateBaseFiles(string $projectType, array $answers): void
    {
        if ($projectType === 'laravel') {
            self::createLaravelFiles($answers['js'], $answers['css'], $answers['themingMode'], $answers['theme']);
        } else {
            self::createSymfonyFiles($answers['js'], $answers['css'], $answers['themingMode'], $answers['theme']);
        }
    }

    public static function createFlexiwindFiles($jsFolder, $cssFolder, $themingMode, $mainCssFileName, $theme)
    {
        // Create directories if they don't exist
        if (!is_dir($jsFolder)) {
            mkdir($jsFolder, Constants::DIR_PERMISSIONS, true);
        }
        if (!is_dir($cssFolder)) {
            mkdir($cssFolder, Constants::DIR_PERMISSIONS, true);
        }
        $themingFolder = strtolower($themingMode) == 'both' ? '' : strtolower($themingMode) . '.';

        file_put_contents(
            $cssFolder . '/base-colors.css',
            StubStorage::get('themes.tailwind.' . $theme)
        );
        

        file_put_contents(
            $cssFolder . "/$mainCssFileName.css",
            StubStorage::get('css.' . $themingFolder . 'base')
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
            StubStorage::get('css.' . $themingFolder . 'buttons')
        );
        file_put_contents(
            $cssFolder . '/ui-utilities.css',
            StubStorage::get('css.' . $themingFolder . 'utilities')
        );
    }

    private static function createLaravelFiles($jsFolder, $cssFolder, $themingMode, $theme): void
    {
        if (!is_dir('app/Flexiwind')) {
            mkdir('app/Flexiwind', Constants::DIR_PERMISSIONS, true);
        }



        file_put_contents(
            'app/Flexiwind/UiHelper.php',
            StubStorage::get('laravel.ui_helper')
        );

        file_put_contents(
            'app/Flexiwind/ButtonHelper.php',
            StubStorage::get('laravel.button_helper')
        );

        self::createFlexiwindFiles($jsFolder, $cssFolder, $themingMode, 'app', $theme);
        self::createLaravelBaseLayout();
    }


    private static function createSymfonyFiles($jsFolder, $cssFolder, $themingMode, $theme): void
    {
        // to be improved : init stimulus, create required files...

        self::createFlexiwindFiles($jsFolder, $cssFolder, $themingMode, 'styles', $theme);
        self::createSymfonyBaseLayout();
    }

    public static function createLaravelBaseLayout()
    {
        if (!is_dir('resources/views/components/layouts')) {
            mkdir('resources/views/components/layouts', 0755, true);
        }
        file_put_contents(
            'resources/views/components/layouts/base.blade.php',
            StubStorage::get('laravel.app_layout')
        );
    }

    public static function createSymfonyBaseLayout() {}
}
