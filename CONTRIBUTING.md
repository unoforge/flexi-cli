# Contributing to Flexiwind CLI

Thank you for considering a contribution! This guide explains how to set up your environment, propose changes, and submit high‑quality pull requests.

## Code of Conduct

By participating, you agree to uphold a respectful, inclusive environment. Be kind and constructive.

## Getting Started

1. Fork the repository and clone your fork.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Verify the CLI runs locally:
   ```bash
   # via PHP
   php bin/flexi-cli --help
   
   # or if executable bit is set
   ./bin/flexi-cli --help
   ```

## Project Structure Overview

- `bin/flexi-cli`: CLI entry point
- `src/Command/`: Symfony Console commands
- `src/Core/`, `src/Service/`, `src/Installer/`: core logic and helpers
- `stubs/`: template files used by installers/scaffolding
- `docs/`: user documentation (index and per‑command pages)

## Development Workflow

1. Create a feature branch from `dev-cli`:
   ```bash
   git checkout dev-cli
   git pull
   git checkout -b feat/my-change
   ```
2. Make changes with clear, focused commits (see Commit Style below).
3. Run the CLI locally to test your changes (see Testing Changes).
4. Update or add docs where needed (see Documentation below).
5. Open a Pull Request against `dev-cli`.

## Commit Style

Use Conventional Commits to keep history consistent:

- `feat: add something new`
- `fix: resolve a bug`
- `docs: update docs`
- `refactor: improve code without changing behavior`
- `chore: tooling or maintenance`
- `perf: performance improvements`

Examples:
- `feat(init): support --uno flag`
- `fix(add): skip existing files without error`

## Code Style

- Follow PSR-12 coding standards.
- Use strict types and type hints where practical.
- Prefer explicit, descriptive names over abbreviations.
- Handle errors with meaningful messages; avoid silent failures.
- Keep functions small and focused; use early returns to reduce nesting.

If you use an auto‑formatter or linter locally, ensure it does not reformat unrelated code.

## Testing Changes

While there is not yet a formal test suite, please:

- Manually exercise affected commands:
  ```bash
  php bin/flexi-cli init --help
  php bin/flexi-cli add --help
  php bin/flexi-cli build --help
  php bin/flexi-cli validate --help
  php bin/flexi-cli clean:flux --help
  ```
- For file‑writing operations, test inside a temporary sample project directory and verify generated files.
- For network/registry features, prefer using local/test endpoints when possible.

If you add testing utilities or scripts later, document them here and in `composer.json` scripts.

## Documentation

If your change affects behavior, options, or output, update the docs:

- Docs index: `docs/README.md`
- Per‑command pages: `docs/commands/*.md`

When adding a new command:
1. Implement in `src/Command/*Command.php`.
2. Create a new page in `docs/commands/your-command.md` with synopsis, options, behavior, and examples.
3. Link it from `docs/README.md` and the root `readme.md` Documentation section.

## Adding/Updating Stubs

- Place new templates under `stubs/` in the appropriate subfolder.
- Keep stub file names consistent and descriptive.
- Avoid trailing spaces and ensure newline at end of file.

## Pull Request Checklist

- [ ] Branch is up to date with `dev-cli`
- [ ] Conventional Commit messages
- [ ] Code follows PSR‑12 and project style
- [ ] New/changed behavior documented in `docs/`
- [ ] Manually tested relevant CLI commands
- [ ] No unrelated file changes or reformatting

## Reporting Issues

When filing a bug report, include:
- CLI version and PHP version (`php -v`)
- OS and package manager (npm/yarn/pnpm) if relevant
- Exact command(s) run and full output (or a screenshot)
- Minimal reproduction steps or a sample repo if possible

## Release Process (maintainers)

- Merge features into `dev-cli`
- After validation, fast‑forward or merge into `main` with a version bump
- Tag the release and update changelog

Thanks for contributing to Flexiwind CLI!
