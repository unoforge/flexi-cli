<?php

namespace FlexiCli\Service;

use FlexiCli\Core\{StubStorage, Constants};
use FlexiCli\Service\Style\Dark;
use FlexiCli\Service\Style\Light;
use FlexiCli\Service\Style\Both;

class CssStyleCompose
{
  private static function getTheme()
  {
    return <<<'CSS'
@theme inline {
    --font-sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    --radius-ui: var(--ui-radius);
    --radius-card: var(--card-radius);
    --radius-checkbox: var(--checkbox-radius); 
    

    --color-white: var(--color-white);
    --color-dark: var(--color-gray-950);

    --color-primary: var(--primary);
    --color-secondary: var(--secondary);
    --color-accent: var(--accent);
    --color-info: var(--info);
    --color-warning: var(--warning);
    --color-danger: var(--danger);
    --color-success: var(--success);

    --color-fg-title: var(--fg-title);
    --color-fg-subtitle: var(--fg-subtitle);
    --color-fg: var(--fg);
    --color-fg-muted: var(--fg-muted);

    --color-bg: var(--bg);
    --color-bg-subtle: var(--bg-subtle);
    --color-bg-surface: var(--bg-surface);
    --color-bg-muted: var(--bg-muted);
    --color-bg-surface-elevated: var(--bg-surface-elevated);
    --color-card: var(--card);
    --color-card-gray: var(--card-gray);
    --color-popover: var(--bg);
    --color-popover-gray: var(--card-gray);
    --color-overlay: var(--overlay);
    --color-overlay-gray: var(--overlay-gray);
    --color-progressbar: var(--progressbar);

    --color-border-strong: var(--border-strong);
    --color-border-amphasis: var(--border-amphasis);
    --color-border: var(--border);
    --color-border-sub: var(--border-sub);
    --color-border-card: var(--border-card);
    --color-border-input: var(--border-input);
}
CSS;
  }
  public static function get(array $answers, $themingMode, $theme)
  {
    $colors = StubStorage::get('themes.' . $theme);
    $icon = Constants::UI_ICONS[$answers['iconLibrary']];

    $headStyle = "@import \"tailwindcss\";\n@reference \"./flexiwind/base.css\"\n@reference \"./flexiwind/form.css\";\n@reference \"./flexiwind/button.css\";\n@reference \"./flexiwind/ui.css\";\n@reference \"./flexiwind/utils.css\";;\n@reference \"./button-styles.css\";\n@reference \"./ui-utilities.css\";";


    $plugin = "@plugin \"@iconify/tailwind4\" {\n  prefixes: $icon;\n  scale: 1.2;\n}\n";


    $baseStyle = <<<'CSS'
@layer base {
    * {
        text-rendering: optimizeLegibility;
        scrollbar-width: thin;
        scrollbar-color: var(--color-border-strong) transparent;
    }
    html {
        font-variation-settings: normal;
        height: 100%;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        -webkit-tap-highlight-color: transparent;
        scroll-behavior: smooth;
    }
    ::-webkit-scrollbar {
        width: 4px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: var(--color-border-strong);
        border-radius: 4px;
    }
}
CSS;

    $keyFrames = <<<'CSS'
/* For modal animation  */
@keyframes modal-animation-in {
    from {
        opacity: 0;
        transform: translateY(-1.5rem);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes modal-animation-out {
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

    $outputStyle = $headStyle . PHP_EOL . PHP_EOL . PHP_EOL . $plugin . PHP_EOL . PHP_EOL .   PHP_EOL  . $style . PHP_EOL . PHP_EOL . $colors . PHP_EOL . PHP_EOL . CssStyleCompose::getTheme() . PHP_EOL . PHP_EOL . $baseStyle . PHP_EOL . PHP_EOL . $keyFrames;

    return $outputStyle;
  }
}
