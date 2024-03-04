<?php

namespace LinkChecker;

class Url
{
    private static $deleteParams = [];
    private static $domains = [];
    private static $skipDomains = [];

    private $isInternal;
    private $url;

    public static function deleteParams(array $deleteParams): void
    {
        self::$deleteParams = array_unique(array_merge(self::$deleteParams, $deleteParams));
    }

    // Set domains that are being checked.
    public static function setDomains(array $links): void
    {
        foreach ($links as $link) {
            $domain = parse_url($link, PHP_URL_HOST);
            self::$domains[$domain] = true;
        }
    }

    // Set domains to skip.
    public static function skipDomains(array $links): void
    {
        foreach ($links as $link) {
            $domain = parse_url($link, PHP_URL_HOST);
            self::$skipDomains[$domain] = true;
        }
    }

    public function __construct(string $url)
    {
        $this->url = $this->cleanUrl($url);
        $domain = parse_url($this->url, PHP_URL_HOST);
        $this->isInternal = isset(self::$domains[$domain]);
    }

    // Define the function to check the URL
    // @todo Define return type.
    public function check()
    {
        // Skip domains.
        $domain = parse_url($this->url, PHP_URL_HOST);
        if (isset(self::$skipDomains)) {
            return null;
        }

        // Initialize cURL session
        $ch = curl_init($this->url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_NOBODY, true);         // Don't return body
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return output as a string from curl_exec()
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);   // Timeout after 10 seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);          // Maximum execution time

        // Execute cURL session and get the HTTP code
        curl_exec($ch);
        $info = [];
        $info['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info['redirectCount'] = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        $info['effectiveUrl'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        // Close cURL session
        curl_close($ch);

        return $info;
    }

    /**
     * Clean URL by removing skip parameters and fragments.
     */
    protected function cleanUrl(string $url): string
    {
        // Parse the URL and extract its components
        $urlParts = parse_url($url);

        if (isset($urlParts['query'])) {
            // Parse the query string into an associative array
            parse_str($urlParts['query'], $queryParams);

            // Iterate over the parameters to remove and unset them from the query array
            foreach (self::$deleteParams as $param) {
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

        // Reconstruct the URL without the removed parameters
        $newUrl = $urlParts['scheme'] . '://' . $urlParts['host'];
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
}
