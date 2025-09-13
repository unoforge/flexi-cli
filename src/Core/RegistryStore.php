<?php

namespace Flexiwind\Core;

class RegistryStore
{
    protected string $file;
    protected array $data = ['installed' => []];

    public function init()
    {
        $this->file = getcwd() . "/components.json";

        if (file_exists($this->file)) {
            $json = file_get_contents($this->file);
            $this->data = json_decode($json, true) ?: ['installed' => []];
        } else {
            $this->persist();
        }
    }

    public function add(string $name, string $namespace, string $version): void
    {
        foreach ($this->data['installed'] as &$item) {
            if ($item['name'] === $name && $item['namespace'] === $namespace) {
                $item['version'] = $version;
                $this->persist();
                return;
            }
        }
        $this->data['installed'][] = [
            'name' => $name,
            'namespace' => $namespace,
            'version' => $version,
        ];
        $this->persist();
    }

    public function exists(string $name, string $namespace): bool
    {
        return (bool) $this->findIndex($name, $namespace);
    }

    public function getVersion(string $name, string $namespace): ?string
    {
        $index = $this->findIndex($name, $namespace);
        return $index !== null ? $this->data['installed'][$index]['version'] : null;
    }

    public function updateVersion(string $name, string $namespace, string $newVersion): void
    {
        $index = $this->findIndex($name, $namespace);
        if ($index !== null) {
            $this->data['installed'][$index]['version'] = $newVersion;
            $this->persist();
        } else {
            throw new \RuntimeException("Component not found: {$namespace}/{$name}");
        }
    }

    public function all(): array
    {
        return $this->data['installed'];
    }

    protected function findIndex(string $name, string $namespace): ?int
    {
        foreach ($this->data['installed'] as $i => $item) {
            if ($item['name'] === $name && $item['namespace'] === $namespace) {
                return $i;
            }
        }
        return null;
    }

    protected function persist(): void
    {
        file_put_contents($this->file, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
