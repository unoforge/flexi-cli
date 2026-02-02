<?php

namespace FlexiCli\Service;

use FlexiCli\Core\{StubStorage, Constants};
use FlexiCli\Service\Style\Dark;
use FlexiCli\Service\Style\Light;
use FlexiCli\Service\Style\Both;

class CssStyleCompose
{
    public static function get(array $answers, $themingMode, $theme)
    {
        $colors = StubStorage::get('themes.tailwind.' . $theme);
        $icon = Constants::UI_ICONS[$answers['iconLibrary']];

        $headStyle = "@import \"tailwindcss\";\n@reference \"./flexiwind/base.css\"\n@reference \"./flexiwind/form.css\";\n@reference \"./flexiwind/button.css\";\n@reference \"./flexiwind/ui.css\";\n@reference \"./flexiwind/utils.css\";;\n@reference \"./button-styles.css\";\n@reference \"./ui-utilities.css\";";
        //


$plugin = "@plugin \"@iconify/tailwind4\" {\n  prefixes: $icon;\n  scale: 1.2;\n}\n";


$baseStyle = <<<'CSS'
* {
  scrollbar-width: thin !important;
  scrollbar-color: transparent !important;
}
::-webkit-scrollbar {
  width: 0 !important;
  height: 0 !important;
}
::-webkit-scrollbar-track {
  background: transparent !important;
}
::-webkit-scrollbar-thumb {
  background-color: transparent !important;
  border-radius: 0 !important;
  border: none !important;
}

CSS;

$keyFrames = <<<'CSS'
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-1.5rem);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeOut {
  from {
    opacity: 1;
    transform: translateY(0);
  }

  to {
    opacity: 0;
    transform: translateY(-0.75rem);
  }
}
CSS;

$darkOnly = Dark::get();
$lightOnly = Light::get();
$both = Both::get();

$style = strtolower($themingMode) == 'both' ? $both : (strtolower($themingMode) == 'dark' ? $darkOnly : $lightOnly);

$outputStyle = $headStyle . PHP_EOL .PHP_EOL .PHP_EOL . $plugin . PHP_EOL .PHP_EOL . $baseStyle .PHP_EOL . PHP_EOL .  PHP_EOL  . $style['root']. PHP_EOL .PHP_EOL . $colors . PHP_EOL .PHP_EOL . $style['theme'] . PHP_EOL . PHP_EOL . $keyFrames;

return $outputStyle;
    }
}
