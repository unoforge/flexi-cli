# clean:flux

Remove Livewire Flux package and clean up related files.

## Synopsis

```bash
flexi-cli clean:flux [--force|-f]
```

## Options

- `--force, -f`: Skip confirmation prompts

## Behavior

- If not forced, asks for confirmation
- Removes `livewire/flux` via Composer if installed
- Deletes known Flux-related view directories and files under `resources/views`

## Examples

```bash
# Interactive cleanup
flexi-cli clean:flux

# Non-interactive
flexi-cli clean:flux --force
```
