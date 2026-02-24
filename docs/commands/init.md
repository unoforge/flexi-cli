# init

Initialize Flexiwind in your project.

## Synopsis

```bash
flexi-cli init [--new-laravel|-nl] [--new-symfony|-ns] [--no-flexiwind] [--js-path <path>] [--css-path <path>]
```

## Description

Runs an interactive setup to initialize Flexiwind in a new or existing project. Detects framework and package manager, then configures theming and optional UI scaffolding.

If `flexiwind.yaml` already exists and is valid, the command exits early.

## Options

- `--new-laravel, -nl`: Create a new Laravel project
- `--new-symfony, -ns`: Create a new Symfony project
- `--no-flexiwind`: Initialize without Flexiwind UI
- `--js-path <path>`: Path to JavaScript files (default `resources/js`)
- `--css-path <path>`: Path to CSS files (default `resources/css`)

## Examples

```bash
# New Laravel with Tailwind
flexi-cli init --new-laravel

# New Symfony with Tailwind
flexi-cli init --new-symfony

# Initialize in current folder
flexi-cli init
```
