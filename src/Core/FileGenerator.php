<?php

namespace FlexiCli\Core;

use FlexiCli\Core\{StubStorage, Constants};
use FlexiCli\Service\ComposeUnoConfig;
use FlexiCli\Service\CssStyleCompose;
use FlexiCli\Service\UnoUiCompose;

class FileGenerator
{
    public static function generateBaseFiles(string $projectType, array $answers): void
    {
        if ($projectType === 'laravel') {
            self::createLaravelFiles($answers,);
        } else {
            self::createSymfonyFiles($answers);
        }
    }

    public static function createShared($answers = [])
    {
        $jsFolder = $answers['js'];
        $cssFolder = $answers['css'];
        // Create directories if they don't exist
        if (!is_dir($jsFolder)) {
            mkdir($jsFolder, Constants::DIR_PERMISSIONS, true);
        }
        if (!is_dir($cssFolder)) {
            mkdir($cssFolder, Constants::DIR_PERMISSIONS, true);
        }

        if (!is_dir($cssFolder.'/flexiwind')) {
            mkdir($cssFolder.'/flexiwind', Constants::DIR_PERMISSIONS, true);
        }

        file_put_contents(
            $jsFolder . '/flexilla.js',
            StubStorage::get('js.flexilla')
        );
    }

    public static function createFlexiwindFiles($answers, $mainCssFileName)
    {
        $cssFolder = $answers['css'];
        $themingMode = $answers['themingMode'];
        $theme = $answers['theme'];
        // Create directories if they don't exist
        self::createShared($answers);
        $themingFolder = strtolower($themingMode) == 'both' ? '' : strtolower($themingMode) . '.';


        $app_style = CssStyleCompose::get($answers, $themingMode, $theme);


        file_put_contents(
            $cssFolder . "/$mainCssFileName.css",
            $app_style
        );

        file_put_contents(
            $cssFolder . '/flexiwind/base.css',
            StubStorage::get('css.flexiwind.base')
        );
        file_put_contents(
            $cssFolder . '/flexiwind/form.css',
            StubStorage::get('css.flexiwind.form')
        );
        file_put_contents(
            $cssFolder . '/flexiwind/button.css',
            StubStorage::get('css.flexiwind.button')
        );
        file_put_contents(
            $cssFolder . '/flexiwind/ui.css',
            StubStorage::get('css.flexiwind.ui')
        );
        file_put_contents(
            $cssFolder . '/flexiwind/utils.css',
            StubStorage::get('css.flexiwind.utils')
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

    public static function createUnoUiFiles($answers, $mainCssFileName, $framework)
    {
        $cssFolder = $answers['css'];
        $themingMode = $answers['themingMode'];
        $theme = $answers['theme'];
        // Create directories if they don't exist
        self::createShared($answers);

        $postcssConfig = ComposeUnoConfig::getPostCSSConfig($framework);
        $unoConfig = ComposeUnoConfig::get($answers, $themingMode, $framework);

        $app_style = UnoUiCompose::get($themingMode);
        $theme = UnoUiCompose::getTheme($theme);


        file_put_contents(
            'postcss.config.js',
            $postcssConfig
        );
        file_put_contents(
            'uno.config.js',
            $unoConfig
        );
        file_put_contents(
            $cssFolder . '/theme.css',
            $theme
        );
        file_put_contents(
            $cssFolder . "/$mainCssFileName.css",
            $app_style
        );
    }

    private static function createLaravelFiles($answers): void
    {
        $mainCssFileName = 'app'; // Default filename for Laravel
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

        if ($answers['cssFramework'] == 'tailwindcss') {
            self::createFlexiwindFiles($answers, $mainCssFileName);
        } else {
            self::createUnoUiFiles($answers, $mainCssFileName, 'laravel');
        }
        self::createLaravelBaseLayout();
    }


    private static function createSymfonyFiles($answers): void
    {
        // to be improved : init stimulus, create required files...

        $mainCssFileName = 'styles'; // Default filename for Symfony
        if ($answers['cssFramework'] == 'tailwindcss') {
            self::createFlexiwindFiles($answers, $mainCssFileName);
        } else {
            self::createUnoUiFiles($answers, $mainCssFileName, 'symfony');
        }
        self::createSymfonyBaseLayout();
    }

    public static function createLaravelBaseLayout()
    {
        if (!is_dir('resources/views/layouts')) {
            mkdir('resources/views/layouts', 0755, true);
        }
        file_put_contents(
            'resources/views/layouts/base.blade.php',
            StubStorage::get('laravel.app_layout')
        );
    }

    public static function createSymfonyBaseLayout() {}
}
