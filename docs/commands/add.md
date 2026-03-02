# add

Add UI components to your project from component registries.

## Synopsis

```bash
flexi-cli add <components...> [--namespace <ns>] [--skip-deps]
```

- `<components...>`: One or more component names. Supports namespaced format like `@flexiwind/button`.

## Options

- `--namespace <ns>`: Namespace to use for all components (must exist in `flexiwind.yaml` `registries`)
- `--skip-deps`: Skip dependency installation; prints commands to install manually

## Behavior

- Validates `flexiwind.yaml` exists in project root
- Resolves registry source from:
  - `--namespace`
  - Component prefix like `@ns/component`
  - `defaultSource` as fallback
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

# Force a specific registry namespace
flexi-cli add card --namespace=@flexiwind

# Skip dependency installs for now
flexi-cli add @flexiwind/button --skip-deps
```
