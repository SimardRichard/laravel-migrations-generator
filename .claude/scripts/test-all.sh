#!/usr/bin/env bash
set -euo pipefail
echo "[1/4] Pint"; ./vendor/bin/pint --test
echo "[2/4] Larastan"; ./vendor/bin/phpstan analyse --no-progress
echo "[3/4] Pest"; ./vendor/bin/pest --coverage --min=80 --compact
echo "[4/4] Audit"; composer audit --locked || true
