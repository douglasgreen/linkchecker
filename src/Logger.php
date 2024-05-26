<?php

declare(strict_types=1);

namespace LinkChecker;

class Logger
{
    private $cacheDir;

    private int $cacheIndex = 0;

    private $logHandle;

    private $urlHandle;

    private $mapHandle;

    public function __construct(
        string $cacheDir,
        private readonly string $logFile,
        private readonly string $urlFile,
        private readonly string $mapFile
    ) {
        if (! file_exists($cacheDir) || ! is_dir($cacheDir)) {
            throw new \Exception('Directory not found: ' . $cacheDir);
        }

        $this->cacheDir = realpath($cacheDir);
        $this->clearCache();

        $this->logHandle = fopen($this->logFile, 'w');
        if (! $this->logHandle) {
            throw new \Exception('Unable to write to log file: ' . $this->logFile);
        }

        $this->urlHandle = fopen($this->urlFile, 'w');
        if (! $this->urlHandle) {
            throw new \Exception('Unable to write to URL file: ' . $this->urlFile);
        }

        $this->mapHandle = fopen($this->mapFile, 'w');
        if (! $this->mapHandle) {
            throw new \Exception('Unable to write to log file: ' . $this->mapFile);
        }
    }

    /**
     * Get name of cookie jar file.
     */
    public function getCookieJar(string $domain): string
    {
        return $this->cacheDir . '/cookie_' . $domain . '.txt';
    }

    /**
     * Save a cache file.
     */
    public function writeCacheFile(string $file): ?int
    {
        if ($file !== '') {
            ++$this->cacheIndex;
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
        if ($line === '') {
            return;
        }

        fwrite($this->logHandle, $line . "\n");
    }

    /**
     * Write to URL file.
     */
    public function writeUrlRow(array $row): void
    {
        if ($row !== []) {
            fputcsv($this->urlHandle, $row);
        }
    }

    /**
     * Write to map file.
     */
    public function writeMapRow(array $row): void
    {
        if ($row !== []) {
            fputcsv($this->mapHandle, $row);
        }
    }

    /**
     * Clear the cache.
     */
    protected function clearCache(): void
    {
        // Clear files.
        $htmlFiles = glob($this->cacheDir . '/file*.html');

        foreach ($htmlFiles as $htmlFile) {
            if (is_file($htmlFile)) {
                unlink($htmlFile);
            }
        }

        // Clear cookie jars.
        $txtFiles = glob($this->cacheDir . '/cookie*.txt');

        foreach ($txtFiles as $txtFile) {
            if (is_file($txtFile)) {
                unlink($txtFile);
            }
        }
    }
}
