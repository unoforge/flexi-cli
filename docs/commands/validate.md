# validate

Validate registry items against the JSON schema.

## Synopsis

```bash
flexi-cli validate [file] [--item|-i <name>]
```

- `file`: Path to registry file to validate (default `registry.json`)

## Options

- `--item, -i <name>`: Validate a single item by name (for multi-item `registry.json`)

## Behavior

- Loads schema from the configured URL
- Supports validating a single registry item file or a multi-item `registry.json` with `items` array
- Prints detailed errors and marks process as failed if invalid

## Examples

```bash
# Validate default registry.json
flexi-cli validate

# Validate a single item
flexi-cli validate registry.json --item button

# Validate a standalone item file
flexi-cli validate components/button.json
```
