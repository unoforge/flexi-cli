<?php

namespace Flexiwind\Core;

use function Laravel\Prompts\{note, info, warning, spin};

class RegistryBuilder
{
    public function build(string $schemaPath, string $outputDir): void
    {
        if (!file_exists($schemaPath)) {
            throw new \RuntimeException("Schema file not found: $schemaPath");
        }

        $schema = json_decode(file_get_contents($schemaPath), true);
        if (!$schema || !isset($schema['components'])) {
            throw new \RuntimeException("Invalid schema file");
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        foreach ($schema['components'] as $component) {
            $files = [];
            note("Building component: " . $component['name']);

            $registry = [
                '$schema'     => 'https://raw.githubusercontent.com/unoforge/cli/main/registry-item.json',
                'version'     => $component['version'] ?? '0.0.1',
                'name'        => $component['name'],
                'type'        => $component['type'] ?? 'registry:component',
                'title'       => $component['title'] ?? '',
                'description' => $component['description'] ?? '',
            ];

            spin(message: "Building files", callback: function () use ($component, &$files) {
                foreach ($component['files'] as $fileItem) {
                    $filePath = $fileItem['path'];

                    if (!file_exists($filePath)) {
                        warning("⚠️ File not found: {$filePath} — skipping.");
                        continue; // Skip to next file
                    }

                    $files[] = [
                        'path'    => $filePath,
                        'type'    => $fileItem['type'] ?? 'registry:component',
                        'target'  => $fileItem['target'] ?? $filePath,
                        'content' => file_get_contents($filePath),
                    ];
                }
            });

            if (isset($component['registryDependencies'])) {
                if (!is_array($component['registryDependencies'])) {
                    warning("⚠️ `registryDependencies` must be an array for component `{$component['name']}`.");
                } else {
                    $registry['registryDependencies'] = $component['registryDependencies'];
                }
            }

            if (isset($component['dependencies'])) {
                if (!is_array($component['dependencies'])) {
                    warning("⚠️ `dependencies` must be an object (name => version) for component `{$component['name']}`.");
                } else {
                    foreach ($component['dependencies'] as $dep => $version) {
                        if (!is_string($dep) || !is_string($version)) {
                            warning("⚠️ Invalid dependency format in `dependencies` for component `{$component['name']}`.");
                        }
                    }
                    $registry['dependencies'] = $this->normalizeDependencies($component['dependencies'], $component['name'], 'dependencies');
                }
            }

            if (isset($component['devDependencies'])) {
                if (!is_array($component['devDependencies'])) {
                    warning("⚠️ `devDependencies` must be an object (name => version) for component `{$component['name']}`.");
                } else {
                    foreach ($component['devDependencies'] as $dep => $version) {
                        if (!is_string($dep) || !is_string($version)) {
                            warning("⚠️ Invalid dependency format in `devDependencies` for component `{$component['name']}`.");
                        }
                    }
                    $registry['devDependencies'] = $this->normalizeDependencies($component['devDependencies'], $component['name'], 'devDependencies');
                }
            }

            $registry['files'] = $files;

            if (isset($component['patch'])) {
                if (!is_array($component['patch'])) {
                    warning("⚠️ `patch` must be an object (file => modifications[]) for component `{$component['name']}`.");
                } else {
                    foreach ($component['patch'] as $file => $modifications) {
                        if (!is_string($file) || !is_array($modifications)) {
                            warning("⚠️ Invalid patch format for file `{$file}` in component `{$component['name']}`.");
                        }
                    }
                    $registry['patch'] = $component['patch'];
                }
            }

            if (empty($files)) {
                warning("⚠️ No valid files for component: " . $component['name']);
                continue;
            }

            $outputFile = rtrim($outputDir, '/') . '/' . $component['name'] . '.json';
            file_put_contents(
                $outputFile,
                json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            info("✔ " . $component['name'] . " built successfully");
        }
    }

    private function normalizeDependencies(mixed $deps, string $componentName, string $section): array
    {
        $normalized = [];

        if (is_array($deps)) {
            foreach ($deps as $key => $value) {
                if (is_string($key)) {
                    // Cas objet: { "dep": "version" }
                    $normalized[] = "{$key}@{$value}";
                } elseif (is_string($value)) {
                    // Cas array déjà formaté: ["dep@version"]
                    $normalized[] = $value;
                } else {
                    warning("⚠️ Invalid dependency format in `$section` for component `{$componentName}`.");
                }
            }
        } else {
            warning("⚠️ `$section` must be an array or object for component `{$componentName}`.");
        }

        return $normalized;
    }
}
