#!/bin/bash

PHP_VERSION="$1"

if ! [[ "${PHP_VERSION}" =~ 8\.2 ]]; then
  echo "mongodb is only installed from pecl for PHP 8.2, ${PHP_VERSION} detected."
  exit 0;
fi

set +e

pecl install mongodb
echo "extension=mongodb.so" > /etc/php/${PHP_VERSION}/mods-available/mongodb.ini
phpenmod -v ${PHP} -s cli mongodb
