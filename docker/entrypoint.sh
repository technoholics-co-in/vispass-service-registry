#!/bin/sh
set -e

read_secret_into() {
    file_path="$1"
    target_var="$2"

    if [ -n "$file_path" ] && [ -f "$file_path" ]; then
        # shellcheck disable=SC2163
        export "$target_var=$(tr -d '\n\r' < "$file_path")"
    fi
}

apply_secret_files() {
    if [ -n "${DB_PASSWORD_FILE:-}" ]; then
        read_secret_into "$DB_PASSWORD_FILE" DB_PASS
        read_secret_into "$DB_PASSWORD_FILE" DB_PASSWORD
        if [ -z "${GLOBAL_DB_PASS:-}" ]; then
            read_secret_into "$DB_PASSWORD_FILE" GLOBAL_DB_PASS
        fi
    fi

    if [ -n "${GLOBAL_DB_PASSWORD_FILE:-}" ]; then
        read_secret_into "$GLOBAL_DB_PASSWORD_FILE" GLOBAL_DB_PASS
        if [ -z "${DB_PASS:-}" ]; then
            read_secret_into "$GLOBAL_DB_PASSWORD_FILE" DB_PASS
        fi
    fi

    if [ -n "${REPORTING_PG_PASSWORD_FILE:-}" ]; then
        read_secret_into "$REPORTING_PG_PASSWORD_FILE" REPORTING_PG_PASSWORD
        if [ -z "${DB_PASSWORD:-}" ]; then
            read_secret_into "$REPORTING_PG_PASSWORD_FILE" DB_PASSWORD
        fi
    fi

    if [ -n "${MONGO_PASSWORD_FILE:-}" ]; then
        read_secret_into "$MONGO_PASSWORD_FILE" MONGO_PASS
        read_secret_into "$MONGO_PASSWORD_FILE" MONGODB_PASSWORD
    fi

    if [ -n "${MONGODB_PASSWORD_FILE:-}" ]; then
        read_secret_into "$MONGODB_PASSWORD_FILE" MONGODB_PASSWORD
        if [ -z "${MONGO_PASS:-}" ]; then
            read_secret_into "$MONGODB_PASSWORD_FILE" MONGO_PASS
        fi
    fi
}

apply_secret_files

if [ -f /var/www/html/.env ]; then
    set -a
    # shellcheck disable=SC1091
    . /var/www/html/.env
    set +a
fi

apply_secret_files

if [ ! -d /var/www/html/vendor ] && [ -f /var/www/html/composer.json ]; then
    composer install --no-interaction --prefer-dist
fi

exec apache2-foreground
