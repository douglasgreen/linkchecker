<?php

declare(strict_types=1);

namespace DouglasGreen\LinkChecker;

class Link
{
    public const array LINK_TAGS = [
        ['a', 'href'],
        ['audio', 'src'],
        ['embed', 'src'],
        ['iframe', 'src'],
        ['img', 'src'],
        ['link', 'href'],
        ['object', 'data'],
        ['script', 'src'],
        ['source', 'src'],
        ['video', 'src'],
    ];

    public const int MAX_REDIRS = 10;

    public string $effectiveUrl;

    public string $mimeType;

    public int $httpCode;

    public int $redirectCount;

    public function __construct(
        protected readonly Logger $logger,
        public string $url,
        public bool $isInternal
    ) {}

    public function check(): void
    {
        // Initialize a Curl session
        $curlHandle = curl_init();

        // Set Curl options
        curl_setopt($curlHandle, CURLOPT_URL, $this->url);
        curl_setopt($curlHandle, CURLOPT_NOBODY, true);
        curl_setopt($curlHandle, CURLOPT_HEADER, true);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 15);
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, self::MAX_REDIRS);

        if ($this->isInternal) {
            $domain = parse_url($this->url, PHP_URL_HOST);
            if ($domain !== false && $domain !== null) {
                $cookieJar = $this->logger->getCookieJar($domain);
                curl_setopt($curlHandle, CURLOPT_COOKIEFILE, $cookieJar);
                curl_setopt($curlHandle, CURLOPT_COOKIEJAR, $cookieJar);
            }
        }

        // Store cookies

        // Execute the Curl session and capture the response headers
        $headers = curl_exec($curlHandle);

        // Close the Curl session
        curl_close($curlHandle);

        // Split headers into lines
        if (is_string($headers)) {
            $headerLines = explode(PHP_EOL, $headers);
            foreach ($headerLines as $headerLine) {
                // Look for the Content-Type header
                if (stripos($headerLine, 'Content-Type:') === 0) {
                    // Extract the MIME type
                    $parts = explode(':', $headerLine);
                    if (count($parts) > 1) {
                        $this->mimeType = trim($parts[1]);
                    }

                    break;
                }
            }
        }

        $this->httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $this->redirectCount = curl_getinfo($curlHandle, CURLINFO_REDIRECT_COUNT);
        $this->effectiveUrl = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        $logLine = sprintf('Checked %s - %d', $this->url, $this->httpCode);
        if ($this->redirectCount) {
            $logLine .= ' (' . $this->redirectCount . ' -> ' . $this->effectiveUrl . ')';
        }

        $this->logger->writeLogLine($logLine);
    }

    /**
     * Get the list of new links for HTML pages.
     *
     * @return list<string>
     */
    public function getNewUrls(): array
    {
        // Only get new URLs from internal HTML pages that aren't redirect loops
        if (
            ! $this->isInternal ||
            $this->redirectCount === self::MAX_REDIRS ||
            (preg_match('~text/html~', $this->mimeType) === 0)
        ) {
            return [];
        }

        // Initialize cURL session
        $curlHandle = curl_init($this->effectiveUrl);
        if ($curlHandle === false) {
            return [];
        }

        // Set cURL options
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true); // Return output as a string from curl_exec()
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);   // Timeout after 10 seconds
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 15);          // Maximum execution time

        // Execute cURL session and get the content
        $content = curl_exec($curlHandle);

        // Close cURL session
        curl_close($curlHandle);

        if (! is_string($content) || $content === '') {
            return [];
        }

        // @todo Figure out how to only log new effective URLs to cache.
        $fileId = $this->logger->writeCacheFile($content);
        if ($fileId !== null) {
            $this->logger->writeLogLine(sprintf('Cached %s: %s', $fileId, $this->effectiveUrl));
        }

        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($content);

        $newUrls = [];
        foreach (self::LINK_TAGS as $linkTag) {
            [$tag, $attrib] = $linkTag;
            $elements = $domDocument->getElementsByTagName($tag);

            foreach ($elements as $element) {
                $href = trim($element->getAttribute($attrib));
                if ($href === '') {
                    continue;
                }

                $newUrls[] = $href;
            }
        }

        return $newUrls;
    }
}
