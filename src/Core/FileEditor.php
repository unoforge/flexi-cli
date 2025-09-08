<?php

namespace Flexiwind\Core;

class FileEditor
{
    /**
     * Update or create a file with content
     *
     * @param string $filePath Path to the file
     * @param string $content Content to write to the file
     * @param bool $createIfNotExists Whether to create the file if it doesn't exist
     * @return bool True on success
     * @throws \RuntimeException If file operations fail
     */
    public static function updateFileContent(string $filePath, string $content, bool $createIfNotExists = true): bool
    {
        // Validate file path
        if (empty($filePath)) {
            throw new \RuntimeException("File path cannot be empty");
        }

        // Check if file exists
        if (!file_exists($filePath)) {
            if (!$createIfNotExists) {
                throw new \RuntimeException("File does not exist: {$filePath}");
            }

            // Create directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
            }
        }

        // Write content to file
        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to write content to file: {$filePath}");
        }

        return true;
    }

    /**
     * Insert code into a file at a specific position
     *
     * @param string $filePath Path to the file
     * @param string $code Code to insert
     * @param string $position Position where to insert ('top', 'bottom', 'after_line', 'before_line')
     * @param int|null $lineNumber Line number for 'after_line' or 'before_line' positions (1-based)
     * @param string|null $searchPattern Pattern to search for when using 'after_line' or 'before_line'
     * @param bool $createIfNotExists Whether to create the file if it doesn't exist
     * @return bool True on success
     * @throws \RuntimeException If file operations fail
     */
    public static function insertCode(
        string $filePath,
        string $code,
        string $position = 'bottom',
        ?int $lineNumber = null,
        ?string $searchPattern = null,
        bool $createIfNotExists = true
    ): bool {
        // Validate inputs
        if (empty($filePath)) {
            throw new \RuntimeException("File path cannot be empty");
        }

        $validPositions = ['top', 'bottom', 'after_line', 'before_line'];
        if (!in_array($position, $validPositions)) {
            throw new \RuntimeException("Invalid position. Must be one of: " . implode(', ', $validPositions));
        }

        // Handle file creation if it doesn't exist
        if (!file_exists($filePath)) {
            if (!$createIfNotExists) {
                throw new \RuntimeException("File does not exist: {$filePath}");
            }

            // Create directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
            }

            // Create file with the code
            if (file_put_contents($filePath, $code) === false) {
                throw new \RuntimeException("Failed to create file: {$filePath}");
            }
            return true;
        }

        // Read existing file content
        $existingContent = file_get_contents($filePath);
        if ($existingContent === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        $lines = explode("\n", $existingContent);
        $newContent = '';

        switch ($position) {
            case 'top':
                $newContent = $code . "\n" . $existingContent;
                break;

            case 'bottom':
                $newContent = $existingContent . "\n" . $code;
                break;

            case 'after_line':
                $targetLine = self::findTargetLine($lines, $lineNumber, $searchPattern);
                $newContent = self::insertAfterLine($lines, $targetLine, $code);
                break;

            case 'before_line':
                $targetLine = self::findTargetLine($lines, $lineNumber, $searchPattern);
                $newContent = self::insertBeforeLine($lines, $targetLine, $code);
                break;
        }

        // Write updated content back to file
        if (file_put_contents($filePath, $newContent) === false) {
            throw new \RuntimeException("Failed to write updated content to file: {$filePath}");
        }

        return true;
    }

    /**
     * Find the target line number based on line number or search pattern
     *
     * @param array $lines Array of file lines
     * @param int|null $lineNumber Specific line number (1-based)
     * @param string|null $searchPattern Pattern to search for
     * @return int Target line number (0-based)
     * @throws \RuntimeException If target line cannot be found
     */
    private static function findTargetLine(array $lines, ?int $lineNumber, ?string $searchPattern): int
    {
        if ($lineNumber !== null) {
            // Convert to 0-based index
            $targetLine = $lineNumber - 1;
            if ($targetLine < 0 || $targetLine >= count($lines)) {
                throw new \RuntimeException("Line number {$lineNumber} is out of range (file has " . count($lines) . " lines)");
            }
            return $targetLine;
        }

        if ($searchPattern !== null) {
            foreach ($lines as $index => $line) {
                if (strpos($line, $searchPattern) !== false) {
                    return $index;
                }
            }
            throw new \RuntimeException("Search pattern '{$searchPattern}' not found in file");
        }

        throw new \RuntimeException("Either line number or search pattern must be provided for line-specific operations");
    }

    /**
     * Insert code after a specific line
     *
     * @param array $lines Array of file lines
     * @param int $targetLine Target line index (0-based)
     * @param string $code Code to insert
     * @return string Updated file content
     */
    private static function insertAfterLine(array $lines, int $targetLine, string $code): string
    {
        $newLines = array_slice($lines, 0, $targetLine + 1);
        $newLines[] = $code;
        $newLines = array_merge($newLines, array_slice($lines, $targetLine + 1));

        return implode("\n", $newLines);
    }

    /**
     * Insert code before a specific line
     *
     * @param array $lines Array of file lines
     * @param int $targetLine Target line index (0-based)
     * @param string $code Code to insert
     * @return string Updated file content
     */
    private static function insertBeforeLine(array $lines, int $targetLine, string $code): string
    {
        $newLines = array_slice($lines, 0, $targetLine);
        $newLines[] = $code;
        $newLines = array_merge($newLines, array_slice($lines, $targetLine));

        return implode("\n", $newLines);
    }

    /**
     * Append content to a file if it doesn't already exist
     *
     * @param string $filePath Path to the file
     * @param string $content Content to append
     * @param bool $createIfNotExists Whether to create the file if it doesn't exist
     * @return bool True if content was added, false if it already existed
     * @throws \RuntimeException If file operations fail
     */
    public static function appendIfNotExists(string $filePath, string $content, bool $createIfNotExists = true): bool
    {
        if (empty($filePath)) {
            throw new \RuntimeException("File path cannot be empty");
        }

        // Handle file creation if it doesn't exist
        if (!file_exists($filePath)) {
            if (!$createIfNotExists) {
                throw new \RuntimeException("File does not exist: {$filePath}");
            }

            // Create directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
            }

            // Create file with the content
            if (file_put_contents($filePath, $content) === false) {
                throw new \RuntimeException("Failed to create file: {$filePath}");
            }
            return true;
        }

        // Read existing file content
        $existingContent = file_get_contents($filePath);
        if ($existingContent === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        // Check if content already exists
        if (strpos($existingContent, $content) !== false) {
            return false; // Content already exists
        }

        // Append content
        if (file_put_contents($filePath, $existingContent . "\n" . $content) === false) {
            throw new \RuntimeException("Failed to append content to file: {$filePath}");
        }

        return true;
    }

    /**
     * Replace a specific line or pattern in a file
     *
     * @param string $filePath Path to the file
     * @param string $search Search pattern or line content to replace
     * @param string $replacement Replacement content
     * @param bool $useRegex Whether to use regex for search
     * @param bool $replaceAll Whether to replace all occurrences or just the first
     * @return bool True if replacement was made
     * @throws \RuntimeException If file operations fail
     */
    public static function replaceInFile(
        string $filePath,
        string $search,
        string $replacement,
        bool $useRegex = false,
        bool $replaceAll = true
    ): bool {
        if (empty($filePath)) {
            throw new \RuntimeException("File path cannot be empty");
        }

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File does not exist: {$filePath}");
        }

        // Read existing file content
        $existingContent = file_get_contents($filePath);
        if ($existingContent === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        // Perform replacement
        if ($useRegex) {
            $newContent = $replaceAll
                ? preg_replace($search, $replacement, $existingContent)
                : preg_replace($search, $replacement, $existingContent, 1);

            if ($newContent === null) {
                throw new \RuntimeException("Regex replacement failed. Invalid pattern: {$search}");
            }
        } else {
            $newContent = $replaceAll
                ? str_replace($search, $replacement, $existingContent)
                : preg_replace('/' . preg_quote($search, '/') . '/', $replacement, $existingContent, 1);
        }

        // Check if any replacement was made
        if ($newContent === $existingContent) {
            return false; // No replacement was made
        }

        // Write updated content back to file
        if (file_put_contents($filePath, $newContent) === false) {
            throw new \RuntimeException("Failed to write updated content to file: {$filePath}");
        }

        return true;
    }

    /**
     * Check if a file contains specific content
     *
     * @param string $filePath Path to the file
     * @param string $content Content to search for
     * @param bool $useRegex Whether to use regex for search
     * @return bool True if content is found
     * @throws \RuntimeException If file operations fail
     */
    public static function fileContains(string $filePath, string $content, bool $useRegex = false): bool
    {
        if (empty($filePath)) {
            throw new \RuntimeException("File path cannot be empty");
        }

        if (!file_exists($filePath)) {
            return false;
        }

        // Read file content
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        // Search for content
        if ($useRegex) {
            return preg_match($content, $fileContent) === 1;
        } else {
            return strpos($fileContent, $content) !== false;
        }
    }
}
