#!/bin/env bash

# Read file and directory paths into an array
IFS=$'\n' read -d '' -r -a files < php_paths

# Join array elements with a comma
file_list=$(
    IFS=,
    echo "${files[*]}"
)

# Run PHPMD with the file list
vendor/bin/phpmd "${file_list}" text phpmd.xml
