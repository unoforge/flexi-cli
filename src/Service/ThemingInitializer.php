<?php

namespace Flexiwind\Service;

use Flexiwind\Core\Constants;
use function Laravel\Prompts\{select};

class ThemingInitializer
{
    public function askTheming(string $defaultFramework = '', bool $isFlexiwind=true): array
    {
        $cssFramework = $defaultFramework;
        $themingMode = $theme = '';


        if ($isFlexiwind) {
            if ($cssFramework === '') {
                $cssFramework = select(
                    label: '🎨 Which Styling Framework would you like to use?',
                    options: Constants::CSS_FRAMEWORKS,
                    default: 'tailwindcss',
                );
            }

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
        }else{
            // todo
        }


        return compact('cssFramework', 'theme', 'themingMode');
    }
}
