#!/usr/bin/env bash
set -euo pipefail
composer install --prefer-stable --prefer-dist --no-interaction
[[ -f package.json ]] && npm install --no-fund --no-audit
[[ -f .env ]] || { cp .env.example .env; php artisan key:generate --ansi; }
php artisan migrate --force
php artisan db:seed --force
echo "Setup terminé"
