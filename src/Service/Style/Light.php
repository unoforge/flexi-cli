<?php

namespace FlexiCli\Service\Style;

class Light
{
    public static function get()
    {
        $root = <<<'CSS'
:root {
    --ui-input-focus-outline: var(--color-primary-600);
    --focus-ring: var(--color-primary-500);
    --ui-input-place-holder: var(--color-gray-500);
    --ui-input-invalid-outline: var(--color-danger-600);
    --ring-offset-color: var(--color-white);
}
CSS;
        $theme = <<<'CSS'
@theme inline {
    --radius-ui: var(--radius-lg);
    --radius-card: var(--radius-lg);
    --radius-checkbox: var(--radius);

    --color-primary: var(--color-primary-600);
    --color-secondary: var(--color-secondary-600);
    --color-accent: var(--color-accent-600);
    --color-info: var(--color-info-600);
    --color-warning: var(--color-warning-600);
    --color-danger: var(--color-danger-600);
    --color-success: var(--color-success-600);

    /* background colors  */
    --color-bg: var(--color-white);
    --color-bg-subtle: var(--color-gray-50);
    --color-bg-surface: var(--color-gray-100);
    --color-bg-muted: var(--color-gray-200);
    --color-bg-surface-elevated: var(--color-gray-300);
    --color-card: var(--color-bg);
    --color-card-gray: var(--color-bg-surface);
    --color-popover: var(--color-bg);
    --color-popover-gray: var(--color-bg-surface);
    --color-overlay: var(--color-bg);
    --color-overlay-gray: var(--color-bg-surface);
    --color-progressbar: var(--color-bg-muted);

    /* foreground colors  */
    --color-fg: var(--color-gray-700);
    --color-fg-muted: var(--color-gray-600);
    --color-fg-subtitle: var(--color-gray-800);
    --color-fg-title: var(--color-gray-900);

    /* border colors  */
    --color-border: var(--color-gray-200);
    --color-border-subtle: var(--color-gray-100);
    --color-border-strong: var(--color-gray-300);
    --color-border-amphasis: var(--color-gray-400);
    --color-border-input: var(--color-gray-200);
}

CSS;

        return compact('root', 'theme');
    }
}