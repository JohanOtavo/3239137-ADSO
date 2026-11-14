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

# The first administrator (T-17, AC-02.3). Without this the stack comes up with
# an empty user table and nobody can log in, which is how a working deployment
# still looks broken to whoever opens it. The command is idempotent, so running
# it on every start leaves one administrator and changes no password.
php artisan filmgear:ensure-admin

exec php artisan serve --host=0.0.0.0 --port=8000
