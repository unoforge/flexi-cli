# Flexi CLI Package

`packages/cli` is the full PHP CLI entrypoint package.

- Binary: `bin/flexi-cli`
- Uses shared logic from `unoforge/flexi-core`
- Supports Laravel and Symfony initialization, plus shared registry commands

## Installation

```bash
composer require --dev unoforge/flexi-cli
```

or globaly

```bash
composer global require unoforge/flexi-cli
```


# Commands
## Init

### With Flexiwind (UI)

```bash
flexi-cli init
```
Then follow process

### Without Flexiwind

```bash
flexi-cli init --no-flexiwind
```

## Add Command

```bash
flexi-cli add @my-source/registry-name
```

### Show without adding

```bash
flexi-cli add @my-source/registry-name --dry
```

## Build registries

```bash
flexi-cli build
```

For more information [check here](./docs/README.md)



