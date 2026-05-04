#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if grep -q '^APP_KEY=$' .env; then
    php artisan key:generate --ansi --force
fi

if [ "${DB_CONNECTION:-}" = "pgsql" ]; then
    until nc -z "${DB_HOST:-postgres}" "${DB_PORT:-5432}"; do
        sleep 1
    done
fi

php artisan migrate --force

exec "$@"
