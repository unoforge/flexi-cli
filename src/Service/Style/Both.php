<?php

namespace FlexiCli\Service\Style;

class Both
{
    public static function get()
    {
        $root = <<<'CSS'
:root {
    --ui-input-focus-outline: var(--color-primary);
    --focus-ring: var(--color-primary-200);
    --ui-input-place-holder: var(--color-gray-500);
    --ui-input-invalid-outline: var(--color-danger);
    --ring-offset-color: var(--color-bg);

    --primary: var(--color-primary-600);
    --secondary: var(--color-secondary-600);
    --accent: var(--color-accent-600);
    --info: var(--color-info-600);
    --warning: var(--color-warning-600);
    --danger: var(--color-danger-600);
    --success: var(--color-success-600);

    /* background colors  */
    --bg: var(--color-white);
    --bg-subtle: var(--color-gray-50);
    --bg-surface: var(--color-gray-100);
    --bg-muted: var(--color-gray-200);
    --bg-surface-elevated: var(--color-gray-300);
    --card: var(--bg);
    --card-gray: var(--bg-surface);
    --overlay: var(--bg);
    --overlay-gray: var(--card-gray);
    --progressbar: var(--bg-surface);

    /* foreground colors  */
    --fg: var(--color-gray-700);
    --fg-muted: var(--color-gray-600);
    --fg-subtitle: var(--color-gray-800);
    --fg-title: var(--color-gray-900);

    /* border colors  */
    --border: var(--color-gray-200);
    --border-subtle: var(--color-gray-100);
    --border-strong: var(--color-gray-300);
    --border-amphasis: var(--color-gray-400);
    --border-input: var(--color-gray-200);
    --border-card: var(--border);
}
.dark{
    --focus-ring: --alpha(var(--color-primary-800)/30%);
    --primary: var(--color-primary-500);
    --secondary: var(--color-secondary-500);
    --accent: var(--color-accent-500);
    --info: var(--color-info-500);
    --warning: var(--color-warning-500);
    --danger: var(--color-danger-500);
    --success: var(--color-success);

  /* background colors  */
    --bg: var(--color-gray-950);
    --bg-subtle: var(--color-gray-900);
    --bg-surface: var(--color-gray-800);
    --bg-muted: var(--color-gray-700);
    --bg-surface-elevated: var(--color-gray-600);

  /* foreground colors  */
    --fg: var(--color-gray-300);
    --fg-muted: var(--color-gray-400);
    --fg-subtitle: var(--color-gray-100);
    --fg-title: var(--color-white);

  /* border colors  */
    --border: var(--color-gray-900);
    --border-subtle: var(--color-gray-950);
    --border-strong: var(--color-gray-800);
    --border-amphasis: var(--color-gray-700);
    --border-input: var(--color-gray-800);
}

@media (prefers-color-scheme: dark) {
  .dark:root {
    color-scheme: dark;
  }
}

@custom-variant dark (&:is(.dark *));
CSS;
        $theme = <<<'CSS'

@theme inline {
    --radius-ui: var(--radius-lg);
    --radius-card: var(--radius-lg);
    --radius-checkbox: var(--radius);

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
    --color-border-card: var(--border-card);
    --color-border-input: var(--border-input);
}
CSS;
        return compact('root', 'theme');
    }
}