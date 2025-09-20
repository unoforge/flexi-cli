<?php

namespace FlexiCli\Service;

use FlexiCli\Core\Constants;

class ComposeUnoConfig
{

    private static function getContentConfig($framework = 'laravel')
    {
        $paths = match ($framework) {
            'laravel' => [
                './resources/views/**/*.blade.php',
                './resources/**/*.js',
            ],
            'symfony' => [
                './templates/**/*.html.twig',
                './assets/**/*.js',
            ],
            default => [],
        };

        if (empty($paths)) {
            throw new \InvalidArgumentException("Unsupported framework: $framework");
        }

        $content = json_encode($paths, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return $content;
    }
    public static function get(array $answers, $themingMode,string $framework = 'laravel')
    {
        $icon = Constants::UI_ICONS[$answers['iconLibrary']] ?? 'ph';


        $configImport = <<<JS
import { defineConfig, presetWind3, presetIcons } from "unocss";
import { flexillaPreset } from "@unifydev/flexilla";
import { presetUI } from "@unifydev/preset-ui";
JS;
        $importIcon = 'import icons from "@iconify-json/' . $icon . '";';
        $content = self::getContentConfig($framework);
        $appearance = $themingMode === 'dark'
            ? 'appearance: "dark"'
            : ($themingMode === 'light'
                ? 'appearance: "light"'
                : '');
        $config = <<<JS
export default defineConfig({
    content: {
        filesystem: $content,
    },
    presets: [
        presetWind3({ dark: "class" }),
        presetIcons({
            collections: {
                // Use the `icons` object directly from '@iconify-json/ph'
                ph: icons,
            },
        }),
        presetUI({
            $appearance
        }),
        flexillaPreset(),
    ],
});
JS;

        $outputStyle = $configImport . PHP_EOL . $importIcon . PHP_EOL . PHP_EOL . $config;

        return $outputStyle;
    }

    public static function getPostCSSConfig(string $framework = 'laravel'): string
    {

        $content = self::getContentConfig($framework);
        $postcssConfig = <<<JS
  import UnoCSS from '@unocss/postcss'
  
  export default {
      plugins: [
          UnoCSS({
              content: $content
          }),
      ],
  }
  JS;

        return $postcssConfig;
    }
}
