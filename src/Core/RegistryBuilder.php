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
                '$schema'     => Constants::SCHEMA_REFERENCE,
                'version'     => $component['version'] ?? Constants::DEFAULT_COMPONENT_VERSION,
                'name'        => $component['name'],
                'type'        => $component['type'] ?? Constants::DEFAULT_COMPONENT_TYPE,
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
                    warning("⚠️ `dependencies` must be an object for component `{$component['name']}`.");
                } else {
                    $registry['dependencies'] = $this->normalizeDependencyStructure($component['dependencies'], $component['name'], 'dependencies');
                }
            }

            if (isset($component['devDependencies'])) {
                if (!is_array($component['devDependencies'])) {
                    warning("⚠️ `devDependencies` must be an object for component `{$component['name']}`.");
                } else {
                    $registry['devDependencies'] = $this->normalizeDependencyStructure($component['devDependencies'], $component['name'], 'devDependencies');
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

    private function normalizeDependencyStructure(mixed $deps, string $componentName, string $section): array
    {
        $normalized = [
            'composer' => [],
            'node' => []
        ];

        if (is_array($deps)) {
            // Check if it's already in the new format
            if (isset($deps['composer']) || isset($deps['node'])) {
                // New format: { "composer": [...], "node": [...] }
                if (isset($deps['composer']) && is_array($deps['composer'])) {
                    $normalized['composer'] = $deps['composer'];
                }
                if (isset($deps['node']) && is_array($deps['node'])) {
                    $normalized['node'] = $deps['node'];
                }
            } else {
                // Legacy format - try to auto-detect package types
                foreach ($deps as $key => $value) {
                    if (is_string($key)) {
                        // Object format: { "package": "version" }
                        $packageWithVersion = "{$key}@{$value}";
                        if ($this->isComposerPackage($key)) {
                            $normalized['composer'][] = $packageWithVersion;
                        } else {
                            $normalized['node'][] = $packageWithVersion;
                        }
                    } elseif (is_string($value)) {
                        // Array format: ["package@version"]
                        $packageName = explode('@', $value)[0];
                        if ($this->isComposerPackage($packageName)) {
                            $normalized['composer'][] = $value;
                        } else {
                            $normalized['node'][] = $value;
                        }
                    }
                }
            }
        } else {
            warning("⚠️ `$section` must be an object for component `{$componentName}`.");
        }

        return $normalized;
    }

    private function isComposerPackage(string $packageName): bool
    {
        // PHP packages typically follow vendor/package format
        if (preg_match('/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/', $packageName)) {
            return true;
        }
        
        // Check for common PHP-specific packages
        $phpPrefixes = ['ext-', 'php', 'lib-'];
        foreach ($phpPrefixes as $prefix) {
            if (str_starts_with($packageName, $prefix)) {
                return true;
            }
        }
        
        return false;
    }
}
