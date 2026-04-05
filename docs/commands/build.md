# build

Build registries from `registry.json`.
The `registry.json` must follow th schema in [schema-item.json](../../schema-item.json)

## Synopsis

```bash
flexi-cli build [--output|-o <dir>] [--override|--no-override]
```

## Options

- `--output, -o <dir>`: Output directory relative to current directory (default `public/r`)
- `--override`: Force override components even if version is unchanged
- `--no-override`: Never override components if version is unchanged

## Description

Invokes the registry builder to produce a `registry-item-name.json` file (or files) in the specified output folder.

## Examples

```bash
# Build to default output
flexi-cli build

# Build to a custom directory
flexi-cli build -o build/registries

# Force override all components
flexi-cli build --override

# Skip building unchanged components
flexi-cli build --no-override
```
