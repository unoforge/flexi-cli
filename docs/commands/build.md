# build

Build registries from `registry.json`.
The `registry.json` must follow th schema in [schema-item.json](../../schema-item.json)

## Synopsis

```bash
flexi-cli build [--output|-o <dir>]
```

## Options

- `--output, -o <dir>`: Output directory relative to current directory (default `public/r`)

## Description

Invokes the registry builder to produce a `registry-item-name.json` file (or files) in the specified output folder.

## Examples

```bash
# Build to default output
flexi-cli build

# Build to a custom directory
flexi-cli build -o build/registries
```
