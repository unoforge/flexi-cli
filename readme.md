# Flexiwind CLI

⚡ A modern CLI tool for rapidly scaffolding modern PHP web applications with flexible CSS frameworks and interactive components.

## Overview

Flexiwind CLI is a command-line tool that streamlines the setup of modern web applications by providing:
- **Framework Support**: Laravel and Symfony project initialization
- **CSS Framework Integration**: TailwindCSS and UnoCSS support
- **Interactive Components**: Livewire, Alpine.js, and Stimulus integration
- **Component Registry**: Add pre-built UI components from remote registries
- **Smart Detection**: Automatic project type and package manager detection

## Installation

### Global Installation (Recommended)

```bash
composer global require unoforge/flexi-cli
```

### Local Installation

```bash
composer require --dev unoforge/flexi-cli
```

## Quick Start

### Initialize a New Project

```bash
# Create a new Laravel project with TailwindCSS
flexi-cli init --new-laravel --tailwind

# Create a new Symfony project with UnoCSS
flexi-cli init --new-symfony --uno

# Initialize in existing project or empty project
flexi-cli init
```

### Add Components

```bash
# Add a button component from the default registry
flexi-cli add @flexiwind/button

# Add a modal component
flexi-cli add @flexiwind/modal
```

## Commands

### `init`
Initialize Flexiwind in your project with interactive setup.

**Options:**
- `--new-laravel, -nl`: Create a new Laravel project
- `--new-symfony, -ns`: Create a new Symfony project
- `--tailwind`: Use TailwindCSS
- `--uno`: Use UnoCSS
- `--css-path` : Path to the CSS files
- `--js-path` : Path to the JS files
- `--no-flexiwind` : Initialize without Flexiwind UI


**Examples:**
```bash
flexi-cli init --new-laravel
```

### `add`
Add UI components to your project from component registries.

**Arguments:**
- `component`: Component name in format `@source/name`

**Examples:**
```bash
flexi-cli add @flexiwind/button
flexi-cli add @ui/card
```

## Features

### ✅ Completed Features

- **Project Initialization**: Interactive setup for new and existing projects
- **Framework Detection**: Automatic Laravel/Symfony project detection
- **Package Manager Detection**: Auto-detect npm, yarn, or pnpm
- **CSS Framework Setup**: TailwindCSS and UnoCSS integration
- **Interactive Components**:
  - Livewire integration for Laravel
  - Alpine.js setup
- **Configuration Management**: YAML-based configuration files
- **File Generation**: Base CSS, JS, and layout files


### 🚧 In Progress

- **Dependency Installation**: Automated package installation
- **Config File Editing**: Vite and framework configuration updates
- **Layout Generation**: Framework-specific layout creation and editing
- **Stub System**: Template files for different frameworks and setups
- **Component Registry**: Download and install components from remote sources

### 📋 Planned Features

- **Starter Templates**: Pre-configured project templates
- **Component Library**: Expanded built-in component collection


## Project Structure

```
flexi-cli/
├── bin/
│   └── flexi-cli              # Main CLI executable
├── src/
│   ├── Command/               # CLI commands
│   │   ├── InitCommand.php    # Project initialization
│   │   └── AddCommand.php     # Component addition
│   ├── Core/                  # Core functionality
│   ├── Installer/             # Package installers
│   └── Service/               # Business logic services
├── stubs/                     # Template files
│   ├── css/                   # CSS templates
│   ├── js/                    # JavaScript templates
│   ├── laravel/               # Laravel-specific templates
│   ├── symfony/               # Symfony-specific templates
│   ├── tailwind/              # TailwindCSS templates
│   └── uno/                   # UnoCSS templates
└── composer.json              # Package configuration
```

## Configuration

Flexiwind uses a `flexiwind.yaml` configuration file in your project root:

```yaml
framework: laravel
livewire: true
alpine: false
theme: flexiwind
themeMode: Both
cssFramework: tailwindcss
js_folder: resources/js
css_folder: resources/css
defaultSource: http://localhost:4500/public/r/{name}.json
registries:
  '@flexiwind': http://localhost:4500/public/r/{name}.json

```

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
4. Make your changes
5. Submit a pull request



## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
