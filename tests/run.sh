#!/bin/sh
set -euf

[ -z "${1:-}" ] || export PHP_VERSION="$1"
export COMPOSE_PROJECT_NAME=fatzebra

docker-compose build --force-rm tests
docker-compose run --rm tests
