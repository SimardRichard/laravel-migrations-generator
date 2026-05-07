#!/usr/bin/env bash
set -euo pipefail
STAGED=$(git diff --cached --name-only --diff-filter=ACMR | grep '\.php$' || true)
[[ -z "$STAGED" ]] && exit 0
echo "$STAGED" | xargs ./vendor/bin/pint --test -- || exit 1
echo "$STAGED" | xargs ./vendor/bin/phpstan analyse --no-progress || exit 1
