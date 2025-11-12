<?php

namespace FlexiCli\Service\Style;

class UnoDark
{
    public static function get()
    {
        $root = <<<'CSS'
:root {
  /* Background colors */
  --bg: var(--c-gray-950);
  --bg-subtle: var(--c-gray-900);
  --bg-surface: var(--c-gray-900);
  --bg-muted: var(--c-gray-800);
  --bg-surface-elevated: var(--c-gray-700);

  /* Foreground colors */
  --fg: var(--c-gray-300);
  --fg-title: var(--c-white);
  --fg-subtitle: var(--c-gray-200);
  --fg-muted: var(--c-gray-300);
  --fg-light: var(--c-gray-400);

  /* Border colors */
  --border: var(--c-gray-900);
  --border-light: var(--c-gray-800);
  --border-subtle: var(--c-gray-900);
  --border-strong: var(--c-gray-700);
  --border-emphasis: var(--c-gray-600);
  --input: hsl(var(--c-gray-800) / 0.6);

  --c-primary-DEFAULT: var(--c-primary-300);
  --c-secondary-DEFAULT: var(--c-secondary-300);
  --c-warning-DEFAULT: var(--c-warning-300);
  --c-accent-DEFAULT: var(--c-accent-300);
  --c-success-DEFAULT: var(--c-success-300);
  --c-danger-DEFAULT: var(--c-danger-300);

  --ui-input-focus-outline: var(--c-primary-500);
  --ui-input-invalid-outline: var(--c-danger-500);
  --ui-input-place-holder: var(--c-gray-500);
  --switch-thumb-primary: var(--c-primary-500);
  --switch-checked-thumb-primary: var(--primary-500);

  --ui-checkbox-border: var(--border-light);

  --switch-knob-dark: var(--c-gray-950);

  color-scheme: dark;
}
CSS;
        return $root;
    }
}