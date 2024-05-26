#!/bin/env bash

# Read file and directory paths into an array
IFS=$'\n' read -d '' -r -a files < php_paths

# Run PHPStan with the file list
./vendor/bin/phpstan analyse "${files[@]}"
