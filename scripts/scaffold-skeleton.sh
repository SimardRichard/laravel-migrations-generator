#!/usr/bin/env bash
set -euo pipefail

ROOT="."
TEMPLATE="/var/www/packages/Module"

# Niveau 1 : racine du repo
mkdir -p "$ROOT/Actions" "$ROOT/Config" "$ROOT/Console/Commands"
mkdir -p "$ROOT/Database/Migrations" "$ROOT/Database/Seeders"
mkdir -p "$ROOT/Docs" "$ROOT/DTOs" "$ROOT/Enums" "$ROOT/Events" "$ROOT/Exceptions"
mkdir -p "$ROOT/Http/Controllers" "$ROOT/Http/Requests" "$ROOT/Http/Resources"
mkdir -p "$ROOT/Interfaces" "$ROOT/Jobs"
mkdir -p "$ROOT/lang/en" "$ROOT/lang/fr" "$ROOT/lang/es"
mkdir -p "$ROOT/Livewire" "$ROOT/Models" "$ROOT/Policies" "$ROOT/Providers"
mkdir -p "$ROOT/resources/css" "$ROOT/resources/js" "$ROOT/resources/scss" "$ROOT/resources/views"
mkdir -p "$ROOT/routes" "$ROOT/Rules" "$ROOT/Services" "$ROOT/Traits"
mkdir -p "$ROOT/.github/workflows"

# Niveau 2 : Modules/Migration/
N2="$ROOT/Modules/Migration"
mkdir -p "$N2/Actions" "$N2/Config" "$N2/Console/Commands"
mkdir -p "$N2/Database/Migrations" "$N2/Database/Seeders"
mkdir -p "$N2/Docs" "$N2/DTOs" "$N2/Enums" "$N2/Events" "$N2/Exceptions"
mkdir -p "$N2/Http/Controllers" "$N2/Http/Requests" "$N2/Http/Resources"
mkdir -p "$N2/Interfaces" "$N2/Jobs"
mkdir -p "$N2/lang/en" "$N2/lang/fr" "$N2/lang/es"
mkdir -p "$N2/Livewire" "$N2/Models" "$N2/Policies" "$N2/Providers"
mkdir -p "$N2/resources/css" "$N2/resources/js" "$N2/resources/scss" "$N2/resources/views"
mkdir -p "$N2/routes" "$N2/Rules" "$N2/Services" "$N2/Traits"

# Niveau 3 : Modules/Migration/Modules/{Generator,Extract,Import}/
for SUB in Generator Extract Import; do
    N3="$N2/Modules/$SUB"
    mkdir -p "$N3/Actions" "$N3/Config" "$N3/Console/Commands"
    mkdir -p "$N3/Database/Migrations" "$N3/Database/Seeders"
    mkdir -p "$N3/Docs" "$N3/DTOs" "$N3/Enums" "$N3/Events" "$N3/Exceptions"
    mkdir -p "$N3/Http/Controllers" "$N3/Http/Requests" "$N3/Http/Resources"
    mkdir -p "$N3/Interfaces" "$N3/Jobs"
    mkdir -p "$N3/lang/en" "$N3/lang/fr" "$N3/lang/es"
    mkdir -p "$N3/Livewire" "$N3/Models" "$N3/Policies" "$N3/Providers"
    mkdir -p "$N3/resources/css" "$N3/resources/js" "$N3/resources/scss" "$N3/resources/views"
    mkdir -p "$N3/routes" "$N3/Rules" "$N3/Services" "$N3/Traits"
done

# Dossiers spécifiques au Generator
mkdir -p "$N2/Modules/Generator/Drivers/Resolvers"
mkdir -p "$N2/Modules/Generator/Renderers"
mkdir -p "$N2/Modules/Generator/Stub"
mkdir -p "$N2/Modules/Generator/Writers"
mkdir -p "$N2/Modules/Generator/stubs"

# Dossiers spécifiques Extract/Import
mkdir -p "$N2/Modules/Extract/Formats"
mkdir -p "$N2/Modules/Import/Formats"

# tests/ à la racine
mkdir -p "$ROOT/tests/Fixtures/Sqlite"
mkdir -p "$ROOT/tests/Unit/Schema" "$ROOT/tests/Unit/Enums" "$ROOT/tests/Unit/Exceptions"
mkdir -p "$ROOT/tests/Unit/Resolvers" "$ROOT/tests/Unit/Renderers" "$ROOT/tests/Unit/Stub"
mkdir -p "$ROOT/tests/Unit/Plan" "$ROOT/tests/Unit/Drivers" "$ROOT/tests/Unit/Actions"
mkdir -p "$ROOT/tests/Unit/Writers" "$ROOT/tests/Unit/Support"
mkdir -p "$ROOT/tests/Feature/Cli" "$ROOT/tests/Feature/Sqlite"

# .gitkeep dans tous les dossiers vides à ce niveau
find . -type d -empty -not -path './.git/*' -not -path './.idea/*' -not -path './.remember/*' -not -path './docs/*' -not -path './.claude/*' -exec touch {}/.gitkeep \;

echo "Skeleton scaffold complete."
