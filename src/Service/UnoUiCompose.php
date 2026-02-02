<?php

namespace FlexiCli\Service;

use FlexiCli\Core\{StubStorage};
use FlexiCli\Service\Style\UnoDark;
use FlexiCli\Service\Style\UnoLight;
use FlexiCli\Service\Style\UnoBoth;

class UnoUiCompose
{

  public static function getTheme($theme)
  {
    $colors = StubStorage::get('themes.uno-3.' . $theme);
    return $colors;
  }

  public static function get($themingMode)
  {

    $headStyle = "@import url(./theme.css); \n@import '@unocss/reset/tailwind.css'; \n@unocss;\n";
    $baseStyle = <<<'CSS'
* {
  scrollbar-width: thin !important;
  scrollbar-color: transparent !important;
}
:root{
  --c-white: 0 0% 100%;
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
    $darkOnly = UnoDark::get();
    $lightOnly = UnoLight::get();
    $both = UnoBoth::get();

    $style = strtolower($themingMode) == 'both' ? $both : (strtolower($themingMode) == 'dark' ? $darkOnly : $lightOnly);

    $outputStyle = $headStyle . PHP_EOL . PHP_EOL . PHP_EOL  . PHP_EOL . $baseStyle . PHP_EOL . PHP_EOL .  PHP_EOL . PHP_EOL . $style . PHP_EOL . PHP_EOL . $keyFrames;

    return $outputStyle;
  }
}
