#!/usr/bin/env php
<?php

/**
 * Check URLs.
 * To do:
 * 1. Decide whether to download HTML/Text file body or skip for PDF/etc.
 * 2. Skip data links.
 * 3. Only follow URLs for internal links (isInternal).
 * 4. Allow retries.
 * 5. Produce report.
 * 6. Add unit tests.
 */

use LinkChecker\Url;

require_once __DIR__ . '/../vendor/autoload.php';

if ($argc != 2) {
    die("Usage: " . basename($argv[0]) . " CONFIG_FILE\n");
}

if (!file_exists($argv[1])) {
    die("File not found\n");
}

$config = parse_ini_file($argv[1], true);

$links = $config['links']['link'] ?? [];
if (!$links) {
    die("No links to check\n");
}

$deleteParams = $config['delete_params']['param'] ?? [];

Url::setDomains($links);

Url::deleteParams($deleteParams);

foreach ($links as $link) {
    $url = new Url($link);
    var_dump($url);
}
