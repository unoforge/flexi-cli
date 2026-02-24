<?php

namespace FlexiCli\Service;

use FlexiCli\Core\Constants;
use function Laravel\Prompts\{select};

class ThemingInitializer
{
    public function askTheming(bool $isFlexiwind = true): array
    {
        $cssFramework = 'tailwindcss';
        $themingMode = $theme = $iconLibrary = '';


        if ($isFlexiwind) {
            $theme = select(
                label: '🎨 Which theme would you like to use?',
                options: Constants::THEMES,
                default: 'flexiwind',
            );

            $themingMode  = select(
                label: 'Your theming mode',
                options: Constants::THEMING_MODES,
                default: 'Both',
            );
            $iconLibrary = select(
                label: '🎨 Which Icon Library would you like to use?',
                options: Constants::ICON_LIBRARIES,
                default: 'phosphore',
            );
        }else{
            // todo
        }


        return compact('cssFramework', 'theme', 'themingMode','iconLibrary');
    }
}
