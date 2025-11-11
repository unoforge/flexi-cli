# Development / Test examples

This folder is intended only for development and test purposes. The example projects and instructions here are not meant to be used as production-ready projects — they're provided so you can quickly try commands and verify CLI behavior.

To test a command from this folder run:

- php ../bin/flexi-cli a-command-to-test

If you're inside a project created for testing (one level deeper) run:

- php ../../bin/flexi-cli a-command-to-test

Example — create a new Laravel project with Tailwind CSS:

- php ../bin/flexi-cli init --new-laravel --tailwind


Make the CLI executable

If `bin/flexi-cli` is not executable you can make it executable with chmod. From this `examples/` folder run:

- chmod +x ../bin/flexi-cli

If you're inside a test project one level deeper run:

- chmod +x ../../bin/flexi-cli

You can also run the script with PHP directly (no chmod required):

- php ../bin/flexi-cli a-command-to-test

Notes:

- Adjust the relative path to `bin/flexi-cli` depending on your current working directory.
- These commands assume PHP is available on your PATH.


