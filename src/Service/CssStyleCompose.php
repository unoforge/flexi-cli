<?php

namespace FlexiCli\Service;

use FlexiCli\Core\{StubStorage, Constants};

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

$darkOnly = self::getDarkOnly();
$lightOnly = self::getLightOnly();
$both = self::getBoth();

$style = strtolower($themingMode) == 'both' ? $both : (strtolower($themingMode) == 'dark' ? $darkOnly : $lightOnly);

$outputStyle = $headStyle . PHP_EOL .PHP_EOL .PHP_EOL . $plugin . PHP_EOL .PHP_EOL . $baseStyle .PHP_EOL . PHP_EOL .  PHP_EOL  . $style['root']. PHP_EOL .PHP_EOL . $colors . PHP_EOL .PHP_EOL . $style['theme'];

return $outputStyle;   
    }

    private  static function getDarkOnly(){

      $root = <<<'CSS'
:root {
  --ui-input-focus-outline: var(--color-primary-500);
  --focus-ring: var(--color-primary-600);
  --ui-input-place-holder: var(--color-gray-500);
  --ui-input-invalid-outline: var(--color-danger-500);
  --ring-offset-color: var(--color-gray-950);
  color-scheme: dark;
}
CSS;
        $theme = <<<'CSS'
@theme inline {
  --color-primary: var(--color-primary-500);
  --color-secondary: var(--color-secondary-500);
  --color-accent: var(--color-accent-500);
  --color-info: var(--color-info-500);
  --color-warning: var(--color-warning-500);
  --color-danger: var(--color-danger-500);
  --color-success: var(--color-success-500);


  /* background colors  */
  --color-bg: var(--color-gray-950);
  --color-bg-subtle: var(--color-gray-900);
  --color-bg-surface: var(--color-gray-800);
  --color-bg-muted: var(--color-gray-700);
  --color-bg-surface-elevated: var(--color-gray-600);


  /* foreground colors  */
  --color-fg: var(--color-gray-300);
  --color-fg-muted: var(--color-gray-400);
  --color-fg-subtitle: var(--color-gray-100);
  --color-fg-title: var(--color-white);


  /* border colors  */
  --color-border: var(--color-gray-900);
  --color-border-subtle: var(--color-gray-950);
  --color-border-strong: var(--color-gray-800);
  --color-border-amphasis: var(--color-gray-700);
  --color-border-input: var(--color-gray-800);


  /* card  */
  --color-card: var(--color-gray-950);
  --color-card-gray: var(--color-gray-900);


  /* popover : For Dropdowns & popovers  */
  --color-popover: var(--color-gray-950);
  --color-popover-gray: var(--color-gray-900);

}
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
      return compact('root', 'theme');
    }

    private static function getLightOnly(){
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


    /* card  */
    --color-card: var(--color-white);
    --color-card-gray: var(--color-gray-100);


    /* popover : For Dropdowns & popovers  */
    --color-popover: var(--color-white);
    --color-popover-gray: var(--color-gray-100);
}    
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

return compact('root', 'theme');
    }

    private static function getBoth(){
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
}
.dark{
    --focus-ring: var(--color-primary-600);
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


  /* card  */
    --card: var(--color-gray-950);
    --card-gray: var(--color-gray-900);


  /* popover : For Dropdowns & popovers  */
    --popover: var(--color-gray-950);
  --popover-gray: var(--color-gray-900); 
}

@media (prefers-color-scheme: dark) {
  .dark:root {
    color-scheme: dark;
  }
}

@custom-variant dark (&:is(.dark *));
CSS;
$theme= <<<'CSS'

@theme inline {
    --color-primary: var(--primary);
    --color-secondary: var(--secondary);
    --color-accent: var(--accent);
    --color-info: var(--info);
    --color-warning: var(--warning);
    --color-danger: var(--danger);
    --color-success: var(--success);


    /* background colors  */
    --color-bg: var(--bg);
    --color-bg-subtle: var(--bg-subtle);
    --color-bg-surface: var(--bg-surface);
    --color-bg-muted: var(--bg-muted);
    --color-bg-surface-elevated: var(--bg-surface-elevated);


    /* foreground colors  */
    --color-fg: var(--fg);
    --color-fg-muted: var(--fg-muted);
    --color-fg-subtitle: var(--fg-subtitle);
    --color-fg-title: var(--fg-title);


    /* border colors  */
    --color-border: var(--border);
    --color-border-subtle: var(--border-subtle);
    --color-border-strong: var(--border-strong);
    --color-border-amphasis: var(--border-amphasis);
    --color-border-input: var(--border-input);


    /* card  */
    --color-card: var(--card);
    --color-card-gray: var(--card-gray);


    /* popover : For Dropdowns & popovers  */
    --color-popover: var(--popover);
    --color-popover-gray: var(--color-gray-100);
}  

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
return compact('root', 'theme');
    }
}
