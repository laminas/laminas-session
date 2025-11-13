#!/bin/bash

PHP_VERSION="$1"

if ! [[ "${PHP_VERSION}" =~ 8\.3 ]] && ! [[ "${PHP_VERSION}" =~ 8\.5 ]]; then
  echo "mongodb and xdebug are only installed from pie for PHP 8.3 or 8.5, ${PHP_VERSION} detected."
  exit 0;
fi

set +e

curl -fL --output /tmp/pie.phar https://github.com/php/pie/releases/latest/download/pie.phar \
  && mv /tmp/pie.phar /usr/local/bin/pie \
  && chmod +x /usr/local/bin/pie

pie install mongodb/mongodb-extension

if ! [[ "${PHP_VERSION}" =~ 8\.5 ]]; then
  pie install xdebug/xdebug
  exit 0;
fi

pie install xdebug/xdebug:@alpha
