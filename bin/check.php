#!/usr/bin/env php
<?php
/**
 * Check URLs.
 */

declare(strict_types=1);

use DouglasGreen\LinkChecker\Crawler;
use DouglasGreen\LinkChecker\Logger;
use DouglasGreen\OptParser\OptParser;

require_once __DIR__ . '/../vendor/autoload.php';

$optParser = new OptParser('Link checker', 'Crawl website and check links');

$optParser->addTerm('config-file', 'INFILE', 'Configuration file in INI format')
    ->addUsageAll();

$input = $optParser->parse();

$config = parse_ini_file((string) $input->get('config-file'));

/** Links to check, whose domains are considered internal and to be crawled. */
$links = $config['links'] ?? [];
if (! $links) {
    die('No links to check' . PHP_EOL);
}

/** Parameters to delete from URLs before checking because they are not part of the page request. */
$deleteParams = $config['delete_params'] ?? [];

/** Domains of sites not to check. */
$skipDomains = $config['skip_domains'] ?? [];

/** Skip URLs of pages not to check when host matches and path starts with path */
$skipUrls = $config['skip_urls'] ?? [];

/** Cache directory for HTML files. */
$cacheDir = $config['cache_dir'] ?? '';

/** Log file for program output. */
$logFile = $config['log_file'] ?? '';

/** List of effective URLs and HTTP codes. */
$urlFile = $config['url_file'] ?? '';

/** Site map file. */
$mapFile = $config['map_file'] ?? '';

$logger = new Logger($cacheDir, $logFile, $urlFile, $mapFile);

$crawler = new Crawler($logger, $links, $skipDomains, $skipUrls, $deleteParams);

$crawler->crawl();
