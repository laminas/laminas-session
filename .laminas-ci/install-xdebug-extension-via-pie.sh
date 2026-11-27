#!/bin/bash

PHP_VERSION="$1"

if ! [[ "${PHP_VERSION}" =~ 8\.6 ]]; then
  echo "xdebug is only installed from pie for PHP 8.6, ${PHP_VERSION} detected."
  exit 0;
fi

set +e

curl -fL --output /tmp/pie.phar https://github.com/php/pie/releases/latest/download/pie.phar \
  && mv /tmp/pie.phar /usr/local/bin/pie \
  && chmod +x /usr/local/bin/pie

pie install xdebug/xdebug:@alpha
