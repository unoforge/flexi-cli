<?php

namespace FlexiCli\Service\Style;

class UnoLight
{
    public static function get()
    {
        $root = <<<'CSS'
:root {
  --bg: var(--c-white);
  --bg-subtle: var(--c-gray-50);
  --bg-surface: var(--c-gray-100);
  --bg-muted: var(--c-gray-200);
  --bg-surface-elevated: var(--c-gray-300);

  --fg: var(--c-gray-700);
  --fg-muted: var(--c-gray-600);
  --fg-title: var(--c-gray-900);
  --fg-subtitle: var(--c-gray-800);

  --border: var(--c-gray-200);
  --border-subtle: var(--c-gray-50);
  --border-light: var(--c-gray-200);
  --border-strong: var(--c-gray-300);
  --border-emphasis: var(--c-gray-400);
  --input: hsl(var(--c-gray-200));

  --c-primary-DEFAULT: var(--c-primary-600);
  --c-secondary-DEFAULT: var(--c-secondary-600);
  --c-warning-DEFAULT: var(--c-warning-600);
  --c-accent-DEFAULT: var(--c-accent-600);
  --c-success-DEFAULT: var(--c-success-600);
  --c-danger-DEFAULT: var(--c-danger-600);

  --ui-input-focus-outline: var(--c-primary-600);
  --ui-input-invalid-outline: var(--c-danger-600);
  --ui-input-place-holder: var(--c-gray-500);
  --switch-thumb-primary: var(--c-primary-600);
  --switch-checked-thumb-primary: var(--primary-600);

  --range-thumb-bg-gray: var(--bg);
  --range-track-bg-gray: var(--c-gray-200);

  --switch-checked-thumb-primary: var(--primary-600);

  --switch-knob-white: var(--c-white);

  --switch-knob-dark: var(--c-white);
}
CSS;

        return $root;
    }
}