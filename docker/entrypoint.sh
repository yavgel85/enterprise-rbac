#!/usr/bin/env sh
set -e

cd /var/www/html

ROLE="${CONTAINER_ROLE:-app}"

wait_for_db() {
    echo "[entrypoint] Waiting for database connection..."
    until php artisan db:show >/dev/null 2>&1; do
        sleep 3
    done
    echo "[entrypoint] Database is up."
}

case "$ROLE" in
    app)
        if [ ! -d vendor ]; then
            echo "[entrypoint] Installing PHP dependencies..."
            composer install --no-interaction --prefer-dist --no-progress
        fi

        if [ ! -f .env ]; then
            cp .env.example .env
        fi

        if ! grep -q '^APP_KEY=base64:' .env; then
            php artisan key:generate --force
        fi

        wait_for_db

        echo "[entrypoint] Running migrations..."
        php artisan migrate --force

        # Seed demo data only on a fresh database (seeders are not idempotent).
        TENANT_COUNT="$(php artisan tinker --execute='echo \App\Models\Tenant::count();' 2>/dev/null | tr -dc '0-9')"
        if [ "${TENANT_COUNT:-0}" = "0" ]; then
            echo "[entrypoint] Fresh database detected — seeding demo data..."
            php artisan db:seed --force
        fi

        # node_modules lives in a container-local volume (Linux-native binaries),
        # so check for the actual binary rather than the directory. Use `npm ci`
        # so the bind-mounted package-lock.json is never rewritten.
        if [ ! -x node_modules/.bin/vite ]; then
            echo "[entrypoint] Installing JS dependencies..."
            npm ci
        fi

        if [ ! -d public/build ]; then
            echo "[entrypoint] Building front-end assets..."
            npm run build
        fi

        php artisan config:clear
        echo "[entrypoint] Starting php-fpm."
        exec php-fpm
        ;;

    queue|scheduler)
        # The app container owns dependency install, env and migrations;
        # workers just wait until everything is ready.
        until [ -d vendor ]; do sleep 2; done
        until [ -f .env ] && grep -q '^APP_KEY=base64:' .env; do sleep 2; done
        wait_for_db
        until php artisan migrate:status >/dev/null 2>&1; do sleep 3; done

        if [ "$ROLE" = "queue" ]; then
            echo "[entrypoint] Starting queue worker."
            exec php artisan queue:work --tries=3 --timeout=90 --sleep=2 --max-time=3600
        fi

        echo "[entrypoint] Starting scheduler."
        exec php artisan schedule:work
        ;;

    *)
        exec "$@"
        ;;
esac
