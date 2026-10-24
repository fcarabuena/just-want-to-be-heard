#!/bin/sh
# Container start-up: listen on Render's $PORT and tell Apache the public https address it sits behind.
set -e

PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Without this, Apache's own redirects (e.g. /admin -> /admin/) point at http://host:$PORT, which is not public.
if [ -n "$RENDER_EXTERNAL_HOSTNAME" ]; then
    printf 'ServerName https://%s:443\nUseCanonicalName On\n' "$RENDER_EXTERNAL_HOSTNAME" > /etc/apache2/conf-enabled/public-name.conf
fi

exec apache2-foreground
