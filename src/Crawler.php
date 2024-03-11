<?php

namespace LinkChecker;

use Exception;

class Crawler
{
    private $logger;

    private $deleteParams = [];
    private $internalDomains = [];
    private $skipDomains = [];
    private $skipUrls = [];

    /**
     * @var array <string, true> Site map hashes for unique record checking.
     */
    private $siteMapHashes = [];

    /**
     * @var array<string, Link> URLs to check
     */
    private $urlsChecked = [];

    /**
     * @var array<string, true> URLs to check
     */
    private $urlsToCheck = [];

    public function __construct(Logger $logger, array $urls, array $skipDomains, array $skipUrls, array $deleteParams)
    {
        $this->logger = $logger;
        $this->setDomains($urls);
        $this->setSkipDomains($skipDomains);
        $this->setSkipUrls($skipUrls);
        $this->deleteParams = $deleteParams;

        foreach ($urls as $url) {
            $cleanUrl = $this->cleanUrl($url);
            $this->urlsToCheck[$cleanUrl] = true;
        }
    }

    /**
     * Crawl the URLs recursively and return the list of URLs checked.
     */
    public function crawl(): array
    {
        while ($this->urlsToCheck) {
            // Make a copy of the list of URLs to check and shuffle it.
            $urlsToCheck = array_keys($this->urlsToCheck);
            shuffle($urlsToCheck);

            $this->logger->writeLogLine("URLs to check: " . count($urlsToCheck));

            /**
             * @var array<string, true> The batch of new URLs to copy to $this->urlsToCheck for checking.
             */
            $newUrls = [];

            foreach ($urlsToCheck as $url) {
                $link = new Link($this->logger, $url, $this->isInternal($url));
                $link->check();

                // Clean effective URL.
                $effectiveUrl = $this->cleanUrl($link->effectiveUrl);
                if (!$effectiveUrl) {
                    continue;
                }

                // Skip URLs from domains that are invalid or blocked.
                if (!$this->hasValidDomain($effectiveUrl) || $this->shouldSkip($effectiveUrl)) {
                    continue;
                }

                // Mark cleaned effective URL checked.
                $this->urlsChecked[$effectiveUrl] = $link;

                // Write to URL file.
                $urlRow = [$effectiveUrl, $link->httpCode];
                $this->logger->writeUrlRow($urlRow);

                foreach ($link->getNewUrls() as $newUrl) {
                    $newUrl = $this->cleanUrl($newUrl);
                    if ($newUrl) {
                        $newUrl = $this->rel2abs($newUrl, $effectiveUrl);
                    }

                    // Skip empty URLs.
                    if (!$newUrl) {
                        continue;
                    }

                    // Skip URLs from domains that are invalid or blocked.
                    if (!$this->hasValidDomain($newUrl) || $this->shouldSkip($effectiveUrl)) {
                        continue;
                    }

                    // Write site map row.
                    $hash = md5($effectiveUrl . '|' . $newUrl);
                    if (!isset($this->siteMap[$hash])) {
                        $mapRow = [$effectiveUrl, $newUrl];
                        $this->logger->writeMapRow($mapRow);
                        $this->siteMap[$hash] = true;
                    }

                    // Skip URLs that have already been checked.
                    if (isset($this->urlsChecked[$newUrl]) || isset($this->effectiveUrlsChecked[$newUrl])) {
                        continue;
                    }

                    $newUrls[$newUrl] = true;
                }
            }

            // Copy a batch of new URLs to check.
            $this->urlsToCheck = $newUrls;
        }

        return $this->urlsChecked;
    }

    /**
     * Clean URL by removing skip parameters and fragments.
     */
    protected function cleanUrl(string $url, ?bool $includeQuery = true): string
    {
        // Parse the URL and extract its components
        $urlParts = parse_url($url);

        if (isset($urlParts['query'])) {
            // Parse the query string into an associative array
            parse_str($urlParts['query'], $queryParams);

            // Iterate over the parameters to remove and unset them from the query array
            foreach ($this->deleteParams as $param) {
                foreach ($queryParams as $key => $value) {
                    if (preg_match('/' . $param . '/', $key)) {
                        unset($queryParams[$key]);
                    }
                }
            }

            // Sort parameters to put URL in canonical form.
            ksort($queryParams);

            // Rebuild the query string from the modified array
            $newQueryString = http_build_query($queryParams);
        } else {
            $newQueryString = '';
        }

        // Reject non-HTTPS? URLs.
        if (isset($urlParts['scheme']) && !preg_match('/^https?$/', $urlParts['scheme'])) {
            return '';
        }

        // Reconstruct the URL without the removed parameters
        $newUrl = '';
        if (isset($urlParts['scheme'])) {
            $newUrl .= $urlParts['scheme'] . ':';
        }
        if (isset($urlParts['host'])) {
            $newUrl .= '//' . $urlParts['host'];
        }
        if (isset($urlParts['port'])) {
            $newUrl .= ':' . $urlParts['port'];
        }
        if (isset($urlParts['path'])) {
            $newUrl .= $urlParts['path'];
        }
        if (!empty($newQueryString)) {
            $newUrl .= '?' . $newQueryString;
        }
        return $newUrl;
    }

    /**
     * Check if a domain is valid.
     */
    protected function hasValidDomain(string $url): bool
    {
        $domain = parse_url($url, PHP_URL_HOST);

        // No domain.
        if (!$domain) {
            return false;
        }

        // Domain was marked to skip.
        if (isset($this->skipDomains[$domain])) {
            return false;
        }

        // Validate domain.
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Check if a URL is internal to domains being checked.
     */
    protected function isInternal(string $url): bool
    {
        $domain = parse_url($url, PHP_URL_HOST);
        return isset($this->internalDomains[$domain]);
    }

    /**
     * Make relative path absolute.
     *
     * @see https://stackoverflow.com/questions/4444475/transform-relative-path-into-absolute-url-using-php
     */
    protected function rel2abs(string $rel, string $base): string
    {
        // Return if already absolute URL.
        if (parse_url($rel, PHP_URL_SCHEME) != '') {
            return $rel;
        }

        $base = $this->cleanUrl($base, false);

        // Queries and anchors
        if ($rel[0] == '?') {
            return $base . $rel;
        } elseif ($rel[0] == '#') {
            return '';
        }

        // Parse base URL and convert to local variables: $scheme, $host, $path
        $parts = parse_url($base);

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';

        // Remove non-directory element from path.
        $path = preg_replace('#/[^/]*$#', '', $path);

        // Destroy path if relative URL points to root.
        if ($rel[0] == '/') {
            $path = '';
        }

        // Make the dirty absolute URL.
        $abs = "$host$path/$rel";

        // Replace '//' or '/./' or '/foo/../' with '/'.
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        $n = 1;
        while ($n > 0) {
            $abs = preg_replace($re, '/', $abs, -1, $n);
        }

        // The absolute URL is ready.
        return $scheme . '://' . $abs;
    }

    /**
     * Set domains that are being checked.
     */
    protected function setDomains(array $urls): void
    {
        foreach ($urls as $url) {
            $domain = parse_url($url, PHP_URL_HOST);
            $this->internalDomains[$domain] = true;
        }
    }

    /**
     * Set domains to skip.
     */
    protected function setSkipDomains(array $urls): void
    {
        foreach ($urls as $url) {
            $domain = parse_url($url, PHP_URL_HOST);
            $this->skipDomains[$domain] = true;
        }
    }

    /**
     * Set URLs (host/path) to skip.
     */
    protected function setSkipUrls(array $urls): void
    {
        foreach ($urls as $url) {
            $parts = parse_url($url);
            $domain = $parts['host'];
            $path = $parts['path'] ?? '/';
            $this->skipUrls[$domain][$path] = true;
        }
    }

    /**
     * Should I skip this domain or domain/path?
     */
    protected function shouldSkip(string $url): bool
    {
        $parts = parse_url($url);
        $domain = $parts['host'];
        $path = $parts['path'] ?? '/';

        if (isset($this->skipDomains[$domain])) {
            return true;
        }

        if (isset($this->skipUrls[$domain][$path])) {
            return true;
        }

        return false;
    }
}
