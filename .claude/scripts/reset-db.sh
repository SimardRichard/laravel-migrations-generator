#!/usr/bin/env bash
set -euo pipefail
ENV=$(php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; echo \$a->environment();")
[[ "$ENV" == "local" ]] || { echo "APP_ENV=$ENV - refus"; exit 1; }
read -r -p "Détruire la BD locale? (oui): " c
[[ "$c" == "oui" ]] || exit 0
php artisan migrate:fresh --seed --force
