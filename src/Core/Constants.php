<?php

namespace FlexiCli\Core;

/**
 * Application constants for Flexiwind CLI
 */
class Constants
{
    /**
     * Default registry URL for component fetching
     */
    public const DEFAULT_REGISTRY = 'https://raw.githubusercontent.com/unoforge/flexiwind/refs/heads/main/public/r/{name}.json';

    /**
     * Local development registry URL
     */
    public const LOCAL_REGISTRY = 'https://raw.githubusercontent.com/unoforge/flexiwind/refs/heads/main/public/r/{name}.json';

    /**
     * Official Flexiwind registry namespace
     */
    public const FLEXIWIND_NAMESPACE = '@flexiwind';

    /**
     * Schema URLs
     */
    public const SCHEMA_URL = 'https://raw.githubusercontent.com/unoforge/cli/main/registry-item.json';
    public const SCHEMA_REFERENCE = 'https://raw.githubusercontent.com/unoforge/cli/main/registry-item.json';

    /**
     * Default configuration file name
     */
    public const CONFIG_FILE = 'flexiwind.yaml';

    /**
     * Default schema file name
     */
    public const SCHEMA_FILE = 'flexiwind.schema.json';

    /**
     * Default output directory for build command
     */
    public const DEFAULT_BUILD_OUTPUT = 'public/r';

    /**
     * Supported CSS frameworks
     */
    public const CSS_FRAMEWORKS = ['tailwindcss', 'unocss'];

    /**
     * Supported icon libraries
     */
    public const ICON_LIBRARIES = ['phosphore', 'heroicons', 'lucide','hugeicons'];

    public const UI_ICONS = [
        'phosphore'=>'ph',
        'heroicons'=>'heroicons',
        'lucide'=>'lucide',
        'hugeicons'=>'hugeicons',
    ];


    /**
     * Supported themes
     */
    public const THEMES = ['flexiwind', 'water', 'earth', 'fire', 'air'];

    /**
     * Supported theming modes
     */
    public const THEMING_MODES = ['Light', 'Dark', 'Both'];

    /**
     * Supported project types
     */
    public const PROJECT_TYPES = ['laravel', 'symfony'];

    /**
     * Default folder paths
     */
    public const DEFAULT_LARAVEL_CSS_PATH = 'resources/css';
    public const DEFAULT_LARAVEL_JS_PATH = 'resources/js';
    public const DEFAULT_SYMFONY_CSS_PATH = 'assets/styles';
    public const DEFAULT_SYMFONY_JS_PATH = 'assets/js';

    /**
     * Component types
     */
    public const COMPONENT_TYPES = [
        'registry:block',
        'registry:script',
        'registry:component',
        'registry:ui',
        'registry:lib',
        'registry:example',
        'registry:style',
        'registry:config'
    ];


    /**
     * Default component type
     */
    public const DEFAULT_COMPONENT_TYPE = 'registry:component';

    /**
     * Default component version
     */
    public const DEFAULT_COMPONENT_VERSION = '0.0.1';

    /**
     * HTTP status codes
     */
    public const HTTP_OK = 200;

    /**
     * File permissions
     */
    public const DIR_PERMISSIONS = 0755;
    public const FILE_PERMISSIONS = 0644;
}
