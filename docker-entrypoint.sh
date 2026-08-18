#!/bin/sh
# Railway assigns a random $PORT at runtime and routes traffic
# to it. The php:apache image listens on 80 by default, so we
# rewrite Apache's port config to match before starting.
set -e

: "${PORT:=80}"

sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

exec "$@"
