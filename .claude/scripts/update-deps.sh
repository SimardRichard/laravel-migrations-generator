#!/usr/bin/env bash
set -euo pipefail
composer update --prefer-stable --prefer-dist --with-all-dependencies
[[ -f package.json ]] && npm update --no-fund --no-audit && npm outdated || true
composer audit || true
