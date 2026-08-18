#!/bin/sh
# Railway assigns a random $PORT at runtime and routes traffic
# to it. The php:apache image listens on 80 by default, so we
# rewrite Apache's port config to match before starting.
set -e

: "${PORT:=80}"

sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# mod_php only works with the prefork MPM. On Railway specifically,
# something in the platform's runtime re-enables mpm_event after the
# image is built, even when it was correctly disabled at build time,
# which crashes Apache with "AH00534: More than one MPM loaded."
# Fixing this at container startup (here, every boot) instead of only
# at build time is what actually makes it stick.
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf
a2enmod mpm_prefork >/dev/null 2>&1 || true
apache2ctl -t

exec "$@"
