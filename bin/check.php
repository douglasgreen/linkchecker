#!/usr/bin/env php
<?php

/**
 * @file Check URLs.
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
 * @var array Domains of sites not to check.
 */
$skipDomains = $config['skip_domains'] ?? [];

/**
 * @var array Skip URLs of pages not to check when host matches and path starts with path
 */
$skipUrls = $config['skip_urls'] ?? [];

/**
 * @var string Cache directory for HTML files.
 */
$cacheDir = $config['cache_dir'];

/**
 * @var string Log file for program output.
 */
$logFile = $config['log_file'];

/**
 * @var string List of effective URLs and HTTP codes.
 */
$urlFile = $config['url_file'];

/**
 * @var string Site map file.
 */
$mapFile = $config['map_file'];

$logger = new Logger($cacheDir, $logFile, $urlFile, $mapFile);

$crawler = new Crawler($logger, $links, $skipDomains, $skipUrls, $deleteParams);

$crawler->crawl();
