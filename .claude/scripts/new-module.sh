#!/usr/bin/env bash
set -euo pipefail
NAME="${1:-}"
[[ -n "$NAME" && "$NAME" =~ ^[A-Z][A-Za-z0-9]*$ ]] || { echo "Usage: $0 <PascalCaseName>"; exit 1; }
SRC=".claude/templates/module-template"; DST="app/Modules/${NAME}"
[[ -d "$SRC" && ! -d "$DST" ]] || { echo "Erreur: source absente ou destination existe"; exit 1; }
cp -R "$SRC" "$DST"
if [[ "$OSTYPE" == "darwin"* ]]; then
    find "$DST" -type f \( -name "*.php" -o -name "*.stub" \) -exec sed -i '' "s/ModuleName/${NAME}/g" {} +
else
    find "$DST" -type f \( -name "*.php" -o -name "*.stub" \) -exec sed -i "s/ModuleName/${NAME}/g" {} +
fi
composer dump-autoload
echo "Module ${NAME} créé. Prochaines étapes :"
echo "  1. Créer ${NAME}ServiceProvider + enregistrer dans bootstrap/providers.php"
echo "  2. Créer ${NAME}Config et Reference Models + migrations + seeders"
echo "  3. php artisan migrate && php artisan db:seed --class=${NAME}Seeder"
