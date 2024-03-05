<?php

namespace LinkChecker;

use Exception;

class Logger
{
    private $cacheDir;
    private $cacheIndex = 0;

    private $logFile;
    private $mapFile;

    private $logHandle;
    private $mapHandle;

    public function __construct(string $cacheDir, string $logFile, string $mapFile)
    {
        if (!file_exists($cacheDir) || !is_dir($cacheDir)) {
            throw new Exception("Directory not found: $cacheDir");
        }

        $this->cacheDir = realpath($cacheDir);
        $this->clearCache();

        $this->logFile = $logFile;
        $this->mapFile = $mapFile;

        $this->logHandle = fopen($this->logFile, 'w');
        if (!$this->logHandle) {
            throw new Exception("Unable to write to log file: $this->logFile");
        }

        $this->mapHandle = fopen($this->mapFile, 'w');
        if (!$this->mapHandle) {
            throw new Exception("Unable to write to log file: $this->mapFile");
        }
    }

    /**
     * Save a cache file.
     */
    public function writeCacheFile(string $file): ?int
    {
        if ($file) {
            $this->cacheIndex++;
            $path = $this->cacheDir . '/file' . $this->cacheIndex . '.html';
            file_put_contents($path, $file);

            return $this->cacheIndex;
        }

        return null;
    }

    /**
     * Write to log file.
     */
    public function writeLogLine(string $line): void
    {
        $line = trim($line);
        if ($line) {
            fputs($this->logHandle, $line . "\n");
        }
    }

    /**
     * Write to map file.
     */
    public function writeMapRow(array $row): void
    {
        if ($row) {
            fputcsv($this->mapHandle, $row);
        }
    }

    /**
     * Clear the cache.
     */
    protected function clearCache(): void
    {
        $htmlFiles = glob($this->cacheDir . '/file*.html');

        foreach ($htmlFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
