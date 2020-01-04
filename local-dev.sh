#!/bin/sh

set -eu

cd "`dirname $0`"

if [ composer.json -nt vendor ]; then
  docker run --rm --interactive --tty \
    --volume $PWD:/app \
    --user "`id -u`:`id -g`" \
    composer install --ignore-platform-reqs
fi

docker build -t local-divera-spreadsheet .
docker run -v "`pwd`":/app -v "`pwd`/data":/app/data -p 8012:80 local-divera-spreadsheet
