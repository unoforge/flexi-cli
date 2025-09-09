<?php

namespace Flexiwind\Core;

use function Laravel\Prompts\{warning, error, info};

class SchemaValidator
{
    private array $schema;
    private array $errors = [];

    public function __construct(?string $schemaPath)
    {
        if (!file_exists($schemaPath)) {
            throw new \RuntimeException("Schema file not found: $schemaPath");
        }

        $this->schema = json_decode(file_get_contents($schemaPath), true);
        if (!$this->schema) {
            throw new \RuntimeException("Invalid schema file: $schemaPath");
        }
    }

    /**
     * Validate a registry item against the schema
     */
    public function validate(array $item): bool
    {
        $this->errors = [];

        // Check required fields
        $this->validateRequired($item);

        // Validate individual fields
        $this->validateName($item);
        $this->validateType($item);
        $this->validateVersion($item);
        $this->validateFiles($item);
        $this->validateDependencies($item);
        $this->validateRegistryDependencies($item);

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Display validation errors using Laravel Prompts
     */
    public function displayErrors(): void
    {
        if (empty($this->errors)) {
            info("✅ Registry item is valid!");
            return;
        }

        error("❌ Registry item validation failed:");
        foreach ($this->errors as $error) {
            warning("  • $error");
        }
    }

    private function validateRequired(array $item): void
    {
        $required = $this->schema['required'] ?? [];

        foreach ($required as $field) {
            if (!isset($item[$field])) {
                $this->errors[] = "Missing required field: $field";
            }
        }
    }

    private function validateName(array $item): void
    {
        if (!isset($item['name'])) {
            return; // Already handled in validateRequired
        }

        $name = $item['name'];

        if (!is_string($name)) {
            $this->errors[] = "Field 'name' must be a string";
            return;
        }

        // Check pattern: ^[a-z0-9-]+$
        if (!preg_match('/^[a-z0-9-]+$/', $name)) {
            $this->errors[] = "Field 'name' must contain only lowercase letters, numbers, and hyphens";
        }

        if (strlen($name) < 1 || strlen($name) > 100) {
            $this->errors[] = "Field 'name' must be between 1 and 100 characters";
        }
    }

    private function validateType(array $item): void
    {
        if (!isset($item['type'])) {
            return; // Already handled in validateRequired
        }

        $type = $item['type'];
        $allowedTypes = [
            "registry:block",
            "registry:script",
            "registry:component",
            "registry:ui",
            "registry:lib",
            "registry:example",
            "registry:style",
            "registry:config"
        ];

        if (!in_array($type, $allowedTypes)) {
            $this->errors[] = "Field 'type' must be one of: " . implode(', ', $allowedTypes);
        }
    }

    private function validateVersion(array $item): void
    {
        if (!isset($item['version'])) {
            return; // Version is optional
        }

        $version = $item['version'];

        if (!is_string($version)) {
            $this->errors[] = "Field 'version' must be a string";
            return;
        }

        // Check semantic versioning pattern
        if (!preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9-]+)?$/', $version)) {
            $this->errors[] = "Field 'version' must follow semantic versioning (e.g., '1.0.0' or '1.0.0-beta')";
        }
    }

    private function validateFiles(array $item): void
    {
        if (!isset($item['files'])) {
            return; // Already handled in validateRequired
        }

        $files = $item['files'];

        if (!is_array($files)) {
            $this->errors[] = "Field 'files' must be an array";
            return;
        }

        if (empty($files)) {
            $this->errors[] = "Field 'files' must contain at least one file";
            return;
        }

        foreach ($files as $index => $file) {
            $this->validateFile($file, $index);
        }
    }

    private function validateFile(array $file, int $index): void
    {
        $prefix = "files[$index]";

        // Required fields for files
        if (!isset($file['path'])) {
            $this->errors[] = "$prefix: Missing required field 'path'";
        } elseif (!is_string($file['path']) || empty($file['path'])) {
            $this->errors[] = "$prefix: Field 'path' must be a non-empty string";
        }

        if (!isset($file['type'])) {
            $this->errors[] = "$prefix: Missing required field 'type'";
        } else {
            $allowedFileTypes = [
                "registry:block",
                "registry:script",
                "registry:component",
                "registry:ui",
                "registry:lib",
                "registry:example",
                "registry:style",
                "registry:config"
            ];

            if (!in_array($file['type'], $allowedFileTypes)) {
                $this->errors[] = "$prefix: Field 'type' must be one of: " . implode(', ', $allowedFileTypes);
            }
        }

        // Optional fields validation
        if (isset($file['target']) && (!is_string($file['target']) || empty($file['target']))) {
            $this->errors[] = "$prefix: Field 'target' must be a non-empty string";
        }

        if (isset($file['content']) && !is_string($file['content'])) {
            $this->errors[] = "$prefix: Field 'content' must be a string";
        }
    }

    private function validateDependencies(array $item): void
    {
        if (!isset($item['dependencies'])) {
            return; // Dependencies are optional
        }

        $dependencies = $item['dependencies'];

        if (!is_array($dependencies)) {
            $this->errors[] = "Field 'dependencies' must be an array";
            return;
        }

        foreach ($dependencies as $index => $dependency) {
            if (!is_string($dependency)) {
                $this->errors[] = "dependencies[$index]: Must be a string";
                continue;
            }

            // Check package@version pattern
            if (!preg_match('/^[a-zA-Z0-9@\/_-]+(@[^@]+)?$/', $dependency)) {
                $this->errors[] = "dependencies[$index]: Invalid format. Expected 'package' or 'package@version'";
            }
        }
    }

    private function validateRegistryDependencies(array $item): void
    {
        if (!isset($item['registryDependencies'])) {
            return; // Registry dependencies are optional
        }

        $dependencies = $item['registryDependencies'];

        if (!is_array($dependencies)) {
            $this->errors[] = "Field 'registryDependencies' must be an array";
            return;
        }

        foreach ($dependencies as $index => $dependency) {
            if (!is_string($dependency)) {
                $this->errors[] = "registryDependencies[$index]: Must be a string";
                continue;
            }

            // Check if it's a local name, scoped package, or URL
            $isLocal = preg_match('/^[a-z0-9-]+$/', $dependency);
            $isScoped = preg_match('/^@[a-z0-9-]+\/[a-z0-9-]+$/', $dependency);
            $isUrl = preg_match('/^https?:\/\//', $dependency);

            if (!$isLocal && !$isScoped && !$isUrl) {
                $this->errors[] = "registryDependencies[$index]: Must be a local name, scoped package (@scope/name), or URL";
            }
        }
    }
}
