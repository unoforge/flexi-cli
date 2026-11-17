# add

Add UI components to your project from component registries.

## Synopsis

```bash
flexi-cli add [<components...>] [--namespace <ns>] [--all] [--skip-deps]
```

- `<components...>`: One or more component names. Supports namespaced format like `@flexiwind/button`.
- Use `@flexiwind` or `@flexiwind/*` to install every registry listed in that source's `index.json`.

## Options

- `--namespace <ns>`: Namespace to use for all components (must exist in `flexiwind.yaml` `registries`)
- `--all`: Install every registry from the source index (`--namespace` or `defaultSource`)
- `--skip-deps`: Skip dependency installation; prints commands to install manually

## Behavior

- Validates `flexiwind.yaml` exists in project root
- Resolves registry source from:
  - `--namespace`
  - Component prefix like `@ns/component`
  - `defaultSource` as fallback
- For `--all` / `@ns` / `@ns/*`, reads the source `index.json` catalog (`type: registry:source`) and installs each listed item
- Installs registry dependencies recursively
- Installs Composer and Node dependencies (asks for confirmation). If declined, shows commands to run later
- Creates files listed by the registry item, skipping existing files
- Collects each component `message` (string or string array) and prints them once at the end in a single note block
- Stores installed component metadata in `components.json`, including normalized `messages` for each installed component when provided

## Examples

```bash
# Add a single component
flexi-cli add @flexiwind/button

# Add multiple components
flexi-cli add @flexiwind/button @flexiwind/modal

# Install everything from a source (via index.json)
flexi-cli add @flexiwind/*
flexi-cli add --all --namespace=@flexiwind

# Force a specific registry namespace
flexi-cli add card --namespace=@flexiwind

# Skip dependency installs for now
flexi-cli add @flexiwind/button --skip-deps
```
