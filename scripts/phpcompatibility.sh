#!/bin/bash
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
phpcs --standard=PHPCompatibility -q -n --colors --runtime-set testVersion "$PHP_VERSION" --extensions=php --ignore=node_modules,vendor,templates_c,.github,scripts . || exit 1;
