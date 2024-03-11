#!/usr/bin/env php
<?php

/**
 * Check URLs.
 * To do:
 * 1. Decide whether to download HTML/Text file body or skip for PDF/etc.
 * 2. Skip data links.
 * 3. Only follow URLs for internal links (isInternal).
 * 4. Add links that point to broken links in report.
 * 5. Add unit tests.
 */

use LinkChecker\Crawler;
use LinkChecker\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

if ($argc != 2) {
    die("Usage: " . basename($argv[0]) . " CONFIG_FILE\n");
}

if (!file_exists($argv[1])) {
    die("File not found\n");
}

$config = parse_ini_file($argv[1]);

/**
 * @var array Links to check, whose domains are considered internal and to be crawled.
 */
$links = $config['links'] ?? [];
if (!$links) {
    die("No links to check\n");
}

/**
 * @var array Parameters to delete from URLs before checking because they are not part of the page request.
 */
$deleteParams = $config['delete_params'] ?? [];

/**
 * @var Domains of sites not to check.
 */
$skipDomains = $config['skip_domains'] ?? [];

/**
 * @var string Cache directory for HTML files.
 */
$cacheDir = $config['cache_dir'];

/**
 * @var string Log file for program output.
 */
$logFile = $config['log_file'];

/**
 * @var string Site map file.
 */
$mapFile = $config['map_file'];

$logger = new Logger($cacheDir, $logFile, $mapFile);

$crawler = new Crawler($logger, $links, $skipDomains, $deleteParams);

$linksChecked = $crawler->crawl();

foreach ($linksChecked as $effectiveUrl => $link) {
    if ($link->httpCode < 200) {
        echo "Bad domain: " . $effectiveUrl . "\n";
    } elseif ($link->httpCode >= 400 && $link->httpCode < 500) {
        echo "Broken link: " . $effectiveUrl . "\n";
    } elseif ($link->httpCode > 500) {
        echo "Site error: " . $effectiveUrl . "\n";
    }
}
