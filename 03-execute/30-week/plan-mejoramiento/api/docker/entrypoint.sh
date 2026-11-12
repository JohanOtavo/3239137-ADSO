#!/bin/sh
set -e

# The application uses no session or cookie encryption, but Laravel expects a
# key. When the environment does not carry one, generate a throwaway for this
# container. It is never written to the repository (article IX).
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
fi

# Migrations own the schema (article V, ADR-001).
php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
