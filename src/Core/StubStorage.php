<?php

namespace FlexiCli\Core;

class StubStorage
{
    private static string $basePath = __DIR__ . '/../../stubs';

    public static function get(string $key): string
    {
        $path = str_replace('.', '/', $key) . '.stub';
        $file = self::$basePath . '/' . $path;

        if (!file_exists($file)) {
            throw new \RuntimeException("Stub not found: {$file}");
        }

        return file_get_contents($file);
    }
}
