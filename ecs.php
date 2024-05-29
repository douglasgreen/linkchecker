<?php

/**
 * Easy Coding Standard (ECS) configuration file
 *
 * This file is used to configure the ECS PHP code style and quality tool.
 *
 * Usage:
 * - To perform risky changes, set the environment variable ECS_RISKY to true.
 *   This should be carefully reviewed to ensure it doesn't break anything.
 * - The file paths for PHP files to analyze come from a file named 'php_paths' in the top-level directory
 *   of the repository. This file should contain PHP files in the top-level directory as well as directories
 *   that contain PHP files. It is shared with other PHP linting utilities so they can all lint the same file list.
 * - The presence of PHPUnit, Symfony, and Doctrine in the composer.json file is automatically detected,
 *   and the relevant ECS rule sets are enabled or disabled accordingly based on the $hasPhpUnit,
 *   $hasSymfony, and $hasDoctrine variables.
 * - The PHP version in composer.json is detected and set as $phpVersion.
 * - Be cautious when configuring the list of annotations to remove using the GeneralPhpdocAnnotationRemoveFixer.
 *   ECS removes both the tag and its contents, whereas in many cases, you may only want to remove or modify
 *   the tag itself without affecting its contents.
 *
 * For more information on configuring ECS, see https://github.com/easy-coding-standard/easy-coding-standard
 */

declare(strict_types=1);

use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$hasPhpUnit = false;
$hasSymfony = false;
$hasDoctrine = false;
$phpVersion = null;

if (file_exists('composer.json')) {
    $composerContent = file_get_contents('composer.json');
    if ($composerContent !== false) {
        $composerData = json_decode($composerContent, true, 16, JSON_THROW_ON_ERROR);

        // Check for PHPUnit, Symfony, and Doctrine
        $requires = $composerData['require'] ?? [];
        $requiresDev = $composerData['require-dev'] ?? [];

        $allDependencies = array_merge($requires, $requiresDev);

        $hasPhpUnit = isset($allDependencies['phpunit/phpunit']);
        foreach ($allDependencies as $name => $value) {
            if (preg_match('#^phpunit/#', $name) === 1) {
                $hasPhpUnit = true;
            }

            if (preg_match('#^symfony/#', $name) === 1) {
                $hasSymfony = true;
            }

            if (preg_match('#^doctrine/#', $name) === 1) {
                $hasDoctrine = true;
            }

            if ($name !== 'php') {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if (preg_match('/\d+\.\d+/', $value, $match) !== 1) {
                continue;
            }

            $phpVersion = $match[0];
        }
    }
}

$php81Migration = false;
$php82Migration = false;
$php83Migration = false;
switch ($phpVersion) {
    case '8.2':
        $php82Migration = true;
        break;
    case '8.3':
        $php83Migration = true;
        break;
    default:
        $php81Migration = true;
        break;
}

// I removed phpCsFixer and phpCsFixerRisky because they conflict with Rector.
$sets = [
    'doctrineAnnotation' => $hasDoctrine,
    'perCS' => true,
    'perCSRisky' => false,
    'php80MigrationRisky' => false,
    'php81Migration' => $php81Migration,
    'php82Migration' => $php82Migration,
    'php83Migration' => $php83Migration,
    'phpunit100MigrationRisky' => false,
    'symfony' => $hasSymfony,
    'symfonyRisky' => false,
];

// To do risky changes, set ECS_RISKY to true in the environment.
$useRisky = (bool) getenv('ECS_RISKY');
if ($useRisky) {
    foreach (array_keys($sets) as $set) {
        if (preg_match('/^phpunit/', $set) === 1) {
            $sets[$set] = $hasPhpUnit;
        } elseif (preg_match('/^symfony/', $set) === 1) {
            $sets[$set] = $hasSymfony;
        } elseif (preg_match('/^doctrine/', $set) === 1) {
            $sets[$set] = $hasDoctrine;
        } else {
            $sets[$set] = true;
        }
    }
}

$paths = file('php_paths');
if ($paths === false) {
    exit("PHP paths not found\n");
}

$paths = array_map('trim', $paths);

return ECSConfig::configure()
    ->withPaths($paths)
    ->withRootFiles()
    ->withPreparedSets(cleanCode: true, common: true, psr12: true, strict: true, symplify: true)
    ->withPhpCsFixerSets(
        doctrineAnnotation: $sets['doctrineAnnotation'],
        perCS: $sets['perCS'],
        perCSRisky: $sets['perCSRisky'],
        php80MigrationRisky: $sets['php80MigrationRisky'],
        php81Migration: $sets['php81Migration'],
        php82Migration: $sets['php82Migration'],
        php83Migration: $sets['php83Migration'],
        phpunit100MigrationRisky: $sets['phpunit100MigrationRisky'],
        symfony: $sets['symfony'],
        symfonyRisky: $sets['symfonyRisky'],
    )
    ->withConfiguredRule(
        // Be careful about this part of the config. ECS removes the tag and its
        // contents when what you often want to do is remove or modify the tag
        // only and not its contents.
        /* @phpstan-ignore-next-line PHPStan can't find this class for some reason */
        GeneralPhpdocAnnotationRemoveFixer::class,
        [
            'annotations' => [
                // Use abstract keyword instead
                'abstract',

                // Use public, protected, or private keyword instead
                'access',

                // Use version history instead
                'author',

                // Use namespaces instead
                'category',

                // Use class keyword instead
                'class',

                // Use @var tag or const keyword instead
                'const',

                // Use constructor keyword instead
                'constructor',

                // Use license file instead
                'copyright',

                // First comment is automatically file comment
                'file',

                // Use final keyword instead
                'final',

                // Use dependency injection instead of globals
                'global',

                // Use @inheritdoc instead
                'inherit',

                // Use license file instead
                'license',

                // Use void return type instead
                'noreturn',

                // Use namespaces instead
                'package',

                // Use @param instead
                'parm',

                // Use private keyword instead
                'private',

                // Use protected keyword instead
                'protected',

                // Use public keyword instead
                'public',

                // Use readonly keyword instead
                'readonly',

                // Use @uses tag instead
                'requires',

                // Use static keyword instead
                'static',

                // Use namespaces instead
                'subpackage',

                // Use type declaration or @var tag instead.
                'type',

                // Use type declaration or @var tag instead.
                'typedef',

                // Use version history instead
                'updated',

                // Use @uses on the other code instead
                'usedby',
            ],
        ]
    );
