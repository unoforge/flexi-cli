<?php

namespace Flexiwind\Service;

use function Laravel\Prompts\{select};

class ThemingInitializer
{
    public function askTheming(string $defaultFramework = ''): array
    {
        $cssFramework = $defaultFramework;

        if ($cssFramework === '') {
            $cssFramework = select(
                label: '🎨 Which Styling Framework would you like to use?',
                options: ['tailwindcss', 'unocss'],
                default: 'tailwindcss',
            );
        }


        $theme = select(
            label: '🎨 Which theme would you like to use?',
            options: ['flexiwind', 'water', 'earth', 'fire', 'air'],
            default: 'flexiwind',
        );

        $themingMode  = select(
            label: 'Your theming mode',
            options: ['Light', 'Dark', 'Both'],
            default: 'Both',
        );


        return compact('cssFramework', 'theme','themingMode');
    }
}
