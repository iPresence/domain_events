#!/bin/bash

if [ $# -eq 0 ]
  then
    VERSIONS="7.1 7.2 7.3";
  else
    VERSIONS="$1";
fi

function test {
  PHP="php-$1"
  $PHP vendor/bin/phpspec run --no-interaction
}

for version in $VERSIONS; do
  echo "Testing PHP $version"
  test $version
done
