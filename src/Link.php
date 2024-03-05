<?php

namespace LinkChecker;

use DOMDocument;

class Link
{
    public const MAX_REDIRS = 10;

    public $url;
    public $isInternal;

    public $effectiveUrl;
    public $httpCode;
    public $mimeType;
    public $redirectCount;

    private $logger;

    public function __construct(Logger $logger, string $url, bool $isInternal)
    {
        $this->logger = $logger;
        $this->url = $url;
        $this->isInternal = $isInternal;
    }

    public function check(): void
    {
        // Initialize a Curl session
        $ch = curl_init();

        // Set Curl options
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_MAXREDIRS, self::MAX_REDIRS);

        // Execute the Curl session and capture the response headers
        $headers = curl_exec($ch);

        // Close the Curl session
        curl_close($ch);

        // Split headers into lines
        $headerLines = explode("\n", $headers);
        foreach ($headerLines as $line) {
            // Look for the Content-Type header
            if (stripos($line, 'Content-Type:') === 0) {
                // Extract the MIME type
                $parts = explode(":", $line);
                if (count($parts) > 1) {
                    $this->mimeType = trim($parts[1]);
                }
                break;
            }
        }

        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        $this->effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $logLine = "Checked $this->url - $this->httpCode";
        if ($this->redirectCount) {
            $logLine .= " (" . $this->redirectCount . " -> " . $this->effectiveUrl . ")";
        }

        $this->logger->writeLogLine($logLine);
    }

    /**
     * Get the list of new links for HTML pages.
     */
    public function getNewUrls(): array
    {
        // Only get new URLs from internal HTML pages that aren't redirect loops
        if (
            !$this->isInternal ||
            $this->redirectCount == self::MAX_REDIRS ||
            !preg_match('~text/html~', $this->mimeType)
        ) {
            return [];
        }

        // Initialize cURL session
        $ch = curl_init($this->effectiveUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return output as a string from curl_exec()
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);   // Timeout after 10 seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);          // Maximum execution time

        // Execute cURL session and get the content
        $content = curl_exec($ch);

        // Close cURL session
        curl_close($ch);

        $fileId = $this->logger->writeCacheFile($content);
        if ($fileId) {
            $this->logger->writeLogLine("Cached $fileId: $this->effectiveUrl");
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $anchors = $dom->getElementsByTagName('a');
        $newUrls = [];

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href) {
                $newUrls[] = $href;
            }
        }

        return $newUrls;
    }
}
