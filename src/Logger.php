<?php

declare(strict_types=1);

namespace DouglasGreen\LinkChecker;

class Logger
{
    protected string $cacheDir;

    protected int $cacheIndex = 0;

    /**
     * @var resource
     */
    protected $logHandle;

    /**
     * @var resource
     */
    protected $urlHandle;

    /**
     * @var resource
     */
    protected $mapHandle;

    public function __construct(
        string $cacheDir,
        protected readonly string $logFile,
        protected readonly string $urlFile,
        protected readonly string $mapFile
    ) {
        if (! file_exists($cacheDir) || ! is_dir($cacheDir)) {
            throw new \Exception('Directory not found: ' . $cacheDir);
        }

        $cacheDir = realpath($cacheDir);
        if ($cacheDir === false) {
            throw new \Exception('Unable to locate cache dir: ' . $cacheDir);
        }

        $this->cacheDir = $cacheDir;
        $this->clearCache();

        $logHandle = fopen($this->logFile, 'w');
        if ($logHandle === false) {
            throw new \Exception('Unable to write to log file: ' . $this->logFile);
        }

        $this->logHandle = $logHandle;

        $urlHandle = fopen($this->urlFile, 'w');
        if ($urlHandle === false) {
            throw new \Exception('Unable to write to URL file: ' . $this->urlFile);
        }

        $this->urlHandle = $urlHandle;

        $mapHandle = fopen($this->mapFile, 'w');
        if ($mapHandle === false) {
            throw new \Exception('Unable to write to log file: ' . $this->mapFile);
        }

        $this->mapHandle = $mapHandle;
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
     *
     * @param array{string, int} $row
     */
    public function writeUrlRow(array $row): void
    {
        fputcsv($this->urlHandle, $row);
    }

    /**
     * Write to map file.
     *
     * @param array{string, string} $row
     */
    public function writeMapRow(array $row): void
    {
        fputcsv($this->mapHandle, $row);
    }

    /**
     * Clear the cache.
     */
    protected function clearCache(): void
    {
        // Clear files.
        $htmlFiles = glob($this->cacheDir . '/file*.html');

        if ($htmlFiles !== false) {
            foreach ($htmlFiles as $htmlFile) {
                if (is_file($htmlFile)) {
                    unlink($htmlFile);
                }
            }
        }

        // Clear cookie jars.
        $txtFiles = glob($this->cacheDir . '/cookie*.txt');

        if ($txtFiles !== false) {
            foreach ($txtFiles as $txtFile) {
                if (is_file($txtFile)) {
                    unlink($txtFile);
                }
            }
        }
    }
}
