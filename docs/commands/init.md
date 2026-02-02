# init

Initialize Flexiwind in your project.

## Synopsis

```bash
flexi-cli init [--new-laravel|-nl] [--new-symfony|-ns] [--tailwind] [--uno] [--no-flexiwind] [--js-path <path>] [--css-path <path>]
```

## Description

Runs an interactive setup to initialize Flexiwind in a new or existing project. Detects framework and package manager, then configures theming and optional UI scaffolding.

If `flexiwind.yaml` already exists and is valid, the command exits early.

## Options

- `--new-laravel, -nl`: Create a new Laravel project
- `--new-symfony, -ns`: Create a new Symfony project
- `--tailwind`: Use TailwindCSS
- `--uno`: Use UnoCSS
- `--no-flexiwind`: Initialize without Flexiwind UI
- `--js-path <path>`: Path to JavaScript files (default `resources/js`)
- `--css-path <path>`: Path to CSS files (default `resources/css`)

## Examples

```bash
# New Laravel with Tailwind
flexi-cli init --new-laravel --tailwind

# New Symfony with UnoCSS
flexi-cli init --new-symfony --uno

# Initialize in current folder
flexi-cli init
```
