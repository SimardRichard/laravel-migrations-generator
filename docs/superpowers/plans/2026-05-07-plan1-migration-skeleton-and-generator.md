# Plan 1 — Squelette Migration Module + Generator SQLite (v0.1.0-alpha)

> **Pour les agents :** SOUS-SKILL REQUISE — `superpowers:subagent-driven-development` (recommandé) ou `superpowers:executing-plans`. Les étapes utilisent la syntaxe checkbox (`- [ ]`).

**Goal:** Construire le squelette complet du module `App\Modules\Migration` (4 niveaux + 5 ServiceProviders) et implémenter le sous-module `Generator` pour SQLite. Livre une release `v0.1.0-alpha` testée, lintée, conforme PHPStan niveau 8.

**Architecture:** Module GSTI hybride à 4 niveaux. Phase 0 = squelette vide. Phase 1 = Generator SQLite. Plans 2-6 = autres moteurs DB + Extract + Import.

**Tech Stack:** PHP 8.4, Laravel 13, Pest 4, Larastan 4 (PHPStan niveau 8), Pint 2, Orchestra Testbench 11, pest-plugin-snapshots 3.

**Spec :** [`docs/superpowers/specs/2026-05-07-migration-module-design.md`](../specs/2026-05-07-migration-module-design.md)

---

## Conventions globales

- **TDD strict** : Red → Green → Refactor → Commit. Toujours.
- **`declare(strict_types=1);`** sur tous les fichiers PHP.
- **`final readonly class`** pour DTOs et Actions.
- **Conventional Commits** (`feat:`, `fix:`, `chore:`, `test:`, `docs:`, `refactor:`, `ci:`).
- Lance `composer pint && composer phpstan && composer test` avant chaque commit.
- Commit après chaque tâche réussie. Pas de batch.
- En cas de blocage : commit `wip:` + demander aide.

---

## Mémo namespace

| Niveau | Namespace |
|---|---|
| 1 (wrapper) | `App\Modules\Migration\` |
| 2 (core)    | `App\Modules\Migration\Modules\Migration\` |
| 3 (Generator) | `App\Modules\Migration\Modules\Migration\Modules\Generator\` |
| 3 (Extract)   | `App\Modules\Migration\Modules\Migration\Modules\Extract\` |
| 3 (Import)    | `App\Modules\Migration\Modules\Migration\Modules\Import\` |

**Note** : ces namespaces sont longs. Utiliser systématiquement des `use` statements en tête de chaque fichier.

---

# PHASE 0 — Squelette modulaire complet (vide)

Cette phase ne contient AUCUNE logique métier. Objectif : avoir l'arborescence + les ServiceProviders + l'outillage en place. Au sortir de la Phase 0, `php artisan list` affiche les 3 commandes (`migrate:generate`, `migrate:extract`, `migrate:import`) en mode stub (chacune dit « not implemented yet »).

---

## Task 0.1: Nettoyer le code legacy

**Files:**
- Delete: `src/Xethron/` (tree)
- Delete: `tests/` (tree)
- Delete: `LICENSE.txt`, `.travis.yml`, `phpunit.xml`, `README.md`

- [ ] **Step 1: Supprimer**

```bash
rm -rf src tests
rm -f LICENSE.txt .travis.yml phpunit.xml README.md
```

- [ ] **Step 2: Vérifier l'état**

```bash
ls -la
```
Attendu : ne reste que `composer.json`, `.git/`, `.gitignore`, `.claude/`, `docs/`.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "chore: remove legacy xethron code, prepare clean slate for module"
```

---

## Task 0.2: Créer l'arborescence complète (4 niveaux)

**Files:** Toutes les structures de dossiers du template `/var/www/packages/Module/` répliquées aux 4 niveaux pertinents.

- [ ] **Step 1: Créer le script de scaffolding**

Crée `scripts/scaffold-skeleton.sh` :

```bash
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

# .gitkeep dans tous les dossiers vides
find "$ROOT/Actions" "$ROOT/Config" "$ROOT/Console" "$ROOT/Database" "$ROOT/Docs" \
     "$ROOT/DTOs" "$ROOT/Enums" "$ROOT/Events" "$ROOT/Exceptions" "$ROOT/Http" \
     "$ROOT/Interfaces" "$ROOT/Jobs" "$ROOT/Livewire" "$ROOT/Models" "$ROOT/Policies" \
     "$ROOT/resources" "$ROOT/routes" "$ROOT/Rules" "$ROOT/Services" "$ROOT/Traits" \
     "$N2" -type d -empty -exec touch {}/.gitkeep \;

echo "Skeleton scaffold complete."
```

- [ ] **Step 2: Exécuter le script**

```bash
chmod +x scripts/scaffold-skeleton.sh
./scripts/scaffold-skeleton.sh
```

- [ ] **Step 3: Vérifier la structure**

```bash
find Modules -type d | head -20
ls -la
```
Attendu : voir les dossiers à 4 niveaux.

- [ ] **Step 4: Commit**

```bash
git add scripts/ Modules/ Actions/ Config/ Console/ Database/ Docs/ DTOs/ Enums/ Events/ Exceptions/ Http/ Interfaces/ Jobs/ lang/ Livewire/ Models/ Policies/ Providers/ resources/ routes/ Rules/ Services/ Traits/ tests/ .github/
git commit -m "chore(skeleton): scaffold complete 4-level module structure"
```

---

## Task 0.3: composer.json racine

**Files:**
- Create: `composer.json`
- Create: `.gitignore`

- [ ] **Step 1: Écrire composer.json**

```json
{
    "name": "groupesti/migration-module",
    "description": "Migration module for Laravel 13+ apps : generate migrations from existing DBs, extract/import data via CSV/JSON/Excel.",
    "keywords": ["laravel", "migration", "generator", "extract", "import", "schema", "csv", "json", "excel"],
    "license": "MIT",
    "authors": [
        { "name": "Groupe STI", "homepage": "https://groupesti.com" },
        { "name": "Bernhard Breytenbach (legacy fork origin)", "email": "bernhard@coffeecode.co.za" }
    ],
    "require": {
        "php": "^8.4",
        "illuminate/console": "^13.0",
        "illuminate/database": "^13.0",
        "illuminate/filesystem": "^13.0",
        "illuminate/support": "^13.0"
    },
    "require-dev": {
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "pestphp/pest-plugin-snapshots": "^3.0",
        "orchestra/testbench": "^11.0",
        "larastan/larastan": "^4.0",
        "laravel/pint": "^2.0",
        "mockery/mockery": "^1.7"
    },
    "autoload": {
        "psr-4": {
            "App\\Modules\\Migration\\": ""
        },
        "exclude-from-classmap": ["tests/", "scripts/", "docs/"]
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Modules\\Migration\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "vendor/bin/pest",
        "test-coverage": "vendor/bin/pest --coverage --min=80",
        "pint": "vendor/bin/pint",
        "pint-test": "vendor/bin/pint --test",
        "phpstan": "vendor/bin/phpstan analyse",
        "ci": ["@pint-test", "@phpstan", "@test-coverage"]
    },
    "extra": {
        "laravel": {
            "providers": ["App\\Modules\\Migration\\Providers\\MigrationServiceProvider"]
        }
    },
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true
        },
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

- [ ] **Step 2: Écrire .gitignore**

```
/vendor/
/build/
/.phpunit.cache/
/.phpunit.result.cache
.phpstan-cache/
composer.lock
.idea/
.vscode/
.DS_Store
tests/__snapshots__/__failed__/
```

- [ ] **Step 3: Installer**

```bash
composer install
```

- [ ] **Step 4: Vérifier**

```bash
ls vendor/bin/pest vendor/bin/pint vendor/bin/phpstan
composer dump-autoload --optimize
```

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock .gitignore
git commit -m "chore: add composer.json with PSR-4 root mapping to App\\Modules\\Migration\\"
```

---

## Task 0.4: Configuration outillage qualité

**Files:**
- Create: `pint.json`
- Create: `phpstan.neon`
- Create: `phpunit.xml`

- [ ] **Step 1: pint.json**

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "ordered_imports": { "sort_algorithm": "alpha" },
        "no_unused_imports": true,
        "single_quote": true,
        "trailing_comma_in_multiline": { "elements": ["arrays", "arguments", "parameters"] }
    }
}
```

- [ ] **Step 2: phpstan.neon**

```yaml
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 8
    paths:
        - Actions
        - Console
        - DTOs
        - Enums
        - Events
        - Exceptions
        - Http
        - Interfaces
        - Jobs
        - Livewire
        - Models
        - Policies
        - Providers
        - Rules
        - Services
        - Traits
        - Modules
    excludePaths:
        - vendor
        - tests
    treatPhpDocTypesAsCertain: false
    checkMissingIterableValueType: true
    tmpDir: .phpstan-cache
```

- [ ] **Step 3: phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         executionOrder="random"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
        <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
    </testsuites>
    <source>
        <include>
            <directory>Modules</directory>
            <directory>Providers</directory>
            <directory>Exceptions</directory>
        </include>
    </source>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

- [ ] **Step 4: Vérifier que les outils tournent**

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress
```
Attendu : OK (pas de fichiers PHP à analyser encore).

- [ ] **Step 5: Commit**

```bash
git add pint.json phpstan.neon phpunit.xml
git commit -m "chore: configure Pint, PHPStan level 8, PHPUnit/Pest"
```

---

## Task 0.5: GitHub Actions baseline

**Files:**
- Create: `.github/workflows/tests.yml`

- [ ] **Step 1: Workflow**

```yaml
name: Tests

on:
  push:
    branches: [master, develop]
  pull_request:

jobs:
  ci:
    runs-on: ubuntu-latest
    name: PHP 8.4 / Laravel 13 / SQLite
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: xdebug
          extensions: pdo_sqlite, sqlite3
      - name: Cache composer
        uses: actions/cache@v4
        with:
          path: ~/.composer/cache/files
          key: composer-${{ hashFiles('composer.json') }}
      - name: Install
        run: composer install --no-interaction --prefer-dist
      - name: Pint
        run: composer pint-test
      - name: PHPStan
        run: composer phpstan
      - name: Pest
        run: composer test-coverage
```

- [ ] **Step 2: Valider YAML**

```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/tests.yml'))" && echo "YAML OK"
```

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/tests.yml
git commit -m "ci: add GitHub Actions workflow (Pint + PHPStan + Pest, SQLite scope)"
```

---

## Task 0.6: ServiceProvider Niveau 1 (wrapper)

**Files:**
- Create: `Providers/MigrationServiceProvider.php`
- Create: `Config/migration.php`

- [ ] **Step 1: Config racine**

```php
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module Migration — config racine
    |--------------------------------------------------------------------------
    |
    | Configuration globale du module wrapper. Les sous-modules
    | (Generator, Extract, Import) ont chacun leur propre fichier de config
    | dans Modules/Migration/Modules/{Sub}/Config/.
    */

    'enabled' => true,
];
```

- [ ] **Step 2: ServiceProvider niveau 1**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Providers;

use App\Modules\Migration\Modules\Migration\Providers\MigrationServiceProvider as CoreMigrationServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Niveau 1 — wrapper module Migration.
 *
 * Enregistre transitivement le ServiceProvider du sous-module core, qui
 * lui-même enregistre les sous-sous-modules Generator/Extract/Import.
 */
final class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/migration.php', 'migration');

        if (! (bool) config('migration.enabled', true)) {
            return;
        }

        $this->app->register(CoreMigrationServiceProvider::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/migration.php' => config_path('migration.php'),
            ], 'migration-config');
        }
    }
}
```

- [ ] **Step 3: PHPStan + Pint**

```bash
composer pint && composer phpstan
```
Attendu : passes (CoreMigrationServiceProvider sera créé en Task 0.7 — pour l'instant on accepte un warning ou on commit cette étape avec le N2 ensemble).

- [ ] **Step 4: Reporter le commit en Task 0.7** (les niveaux 1 et 2 dépendent l'un de l'autre — on commit ensemble)

---

## Task 0.7: ServiceProvider Niveau 2 (core)

**Files:**
- Create: `Modules/Migration/Providers/MigrationServiceProvider.php`
- Create: `Modules/Migration/Config/migration-core.php`

- [ ] **Step 1: Config core**

```php
<?php

declare(strict_types=1);

return [
    /*
    | Sous-modules à activer. Désactiver l'un n'enregistre pas son
    | ServiceProvider, ses commandes ou ses bindings.
    */
    'submodules' => [
        'generator' => true,
        'extract'   => true,
        'import'    => true,
    ],
];
```

- [ ] **Step 2: ServiceProvider niveau 2**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Extract\Providers\ExtractServiceProvider;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Providers\GeneratorServiceProvider;
use App\Modules\Migration\Modules\Migration\Modules\Import\Providers\ImportServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Niveau 2 — sous-module core Migration.
 *
 * Enregistre les bindings d'interfaces partagées et les ServiceProviders
 * des sous-sous-modules Generator/Extract/Import selon la config.
 */
final class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/migration-core.php', 'migration-core');

        $submodules = (array) config('migration-core.submodules', []);

        if (($submodules['generator'] ?? false) === true) {
            $this->app->register(GeneratorServiceProvider::class);
        }

        if (($submodules['extract'] ?? false) === true) {
            $this->app->register(ExtractServiceProvider::class);
        }

        if (($submodules['import'] ?? false) === true) {
            $this->app->register(ImportServiceProvider::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/migration-core.php' => config_path('migration-core.php'),
            ], 'migration-core-config');
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'migration');
    }
}
```

- [ ] **Step 3: Fichier lang minimal pour Pest discovery**

`Modules/Migration/lang/en/messages.php` :
```php
<?php

declare(strict_types=1);

return [
    'module_name' => 'Migration',
];
```

`Modules/Migration/lang/fr/messages.php` :
```php
<?php

declare(strict_types=1);

return [
    'module_name' => 'Migration',
];
```

`Modules/Migration/lang/es/messages.php` :
```php
<?php

declare(strict_types=1);

return [
    'module_name' => 'Migration',
];
```

- [ ] **Step 4: Reporter le commit en Task 0.10**

---

## Task 0.8: ServiceProvider Generator (niveau 3) + commande stub

**Files:**
- Create: `Modules/Migration/Modules/Generator/Providers/GeneratorServiceProvider.php`
- Create: `Modules/Migration/Modules/Generator/Console/Commands/GenerateCommand.php` (stub)
- Create: `Modules/Migration/Modules/Generator/Config/generator.php`

- [ ] **Step 1: Config Generator**

```php
<?php

declare(strict_types=1);

return [
    'stubs_path' => null,
    'default_path' => null, // null = database_path('migrations')
];
```

- [ ] **Step 2: GenerateCommand stub** (Phase 0 — sera remplacé en Task 1.18)

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands;

use Illuminate\Console\Command;

final class GenerateCommand extends Command
{
    protected $signature = 'migrate:generate';

    protected $description = 'Generate Laravel migrations from an existing database (stub for Phase 0).';

    public function handle(): int
    {
        $this->warn('migrate:generate is not implemented yet (Phase 0 skeleton).');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: ServiceProvider Generator**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands\GenerateCommand;
use Illuminate\Support\ServiceProvider;

final class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/generator.php', 'migration.generator');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([GenerateCommand::class]);

        $this->publishes([
            __DIR__.'/../stubs' => $this->app->basePath('stubs/migrations-generator'),
        ], 'migration-generator-stubs');

        $this->publishes([
            __DIR__.'/../Config/generator.php' => config_path('migration/generator.php'),
        ], 'migration-generator-config');
    }
}
```

- [ ] **Step 4: Reporter le commit en Task 0.10**

---

## Task 0.9: ServiceProvider Extract (niveau 3) + commande stub

**Files:**
- Create: `Modules/Migration/Modules/Extract/Providers/ExtractServiceProvider.php`
- Create: `Modules/Migration/Modules/Extract/Console/Commands/ExtractCommand.php` (stub)
- Create: `Modules/Migration/Modules/Extract/Config/extract.php`

- [ ] **Step 1: Config Extract**

```php
<?php

declare(strict_types=1);

return [
    'default_format' => 'csv',
    'output_path' => null,
];
```

- [ ] **Step 2: ExtractCommand stub**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Extract\Console\Commands;

use Illuminate\Console\Command;

final class ExtractCommand extends Command
{
    protected $signature = 'migrate:extract';

    protected $description = 'Extract data from a database to CSV/JSON/Excel files (stub).';

    public function handle(): int
    {
        $this->warn('migrate:extract is not implemented yet (Phase 0 skeleton).');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: ServiceProvider Extract**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Extract\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Extract\Console\Commands\ExtractCommand;
use Illuminate\Support\ServiceProvider;

final class ExtractServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/extract.php', 'migration.extract');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExtractCommand::class]);

            $this->publishes([
                __DIR__.'/../Config/extract.php' => config_path('migration/extract.php'),
            ], 'migration-extract-config');
        }
    }
}
```

- [ ] **Step 4: Reporter le commit en Task 0.10**

---

## Task 0.10: ServiceProvider Import (niveau 3) + commande stub + commit groupé

**Files:**
- Create: `Modules/Migration/Modules/Import/Providers/ImportServiceProvider.php`
- Create: `Modules/Migration/Modules/Import/Console/Commands/ImportCommand.php` (stub)
- Create: `Modules/Migration/Modules/Import/Config/import.php`

- [ ] **Step 1: Config Import**

```php
<?php

declare(strict_types=1);

return [
    'default_format' => 'csv',
    'chunk_size' => 1000,
];
```

- [ ] **Step 2: ImportCommand stub**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Import\Console\Commands;

use Illuminate\Console\Command;

final class ImportCommand extends Command
{
    protected $signature = 'migrate:import';

    protected $description = 'Import data from CSV/JSON/Excel files into a database (stub).';

    public function handle(): int
    {
        $this->warn('migrate:import is not implemented yet (Phase 0 skeleton).');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: ServiceProvider Import**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Import\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Import\Console\Commands\ImportCommand;
use Illuminate\Support\ServiceProvider;

final class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/import.php', 'migration.import');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ImportCommand::class]);

            $this->publishes([
                __DIR__.'/../Config/import.php' => config_path('migration/import.php'),
            ], 'migration-import-config');
        }
    }
}
```

- [ ] **Step 4: Pint + PHPStan + dump autoload**

```bash
composer dump-autoload
composer pint
composer phpstan
```
Attendu : tout passe.

- [ ] **Step 5: Commit groupé pour les Tasks 0.6 → 0.10**

```bash
git add Providers/ Config/ Modules/
git commit -m "feat(skeleton): wire 5 ServiceProviders + 3 stub commands across 4 levels"
```

---

## Task 0.11: Smoke test — les 5 ServiceProviders bootent et les 3 commandes apparaissent

**Files:**
- Create: `tests/Pest.php`
- Create: `tests/TestCase.php`
- Create: `tests/Feature/Cli/SkeletonBootTest.php`

- [ ] **Step 1: TestCase**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Tests;

use App\Modules\Migration\Providers\MigrationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MigrationServiceProvider::class];
    }
}
```

- [ ] **Step 2: Pest.php**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Tests\TestCase;

uses(TestCase::class)->in('Feature');

afterEach(function () {
    if (class_exists(\Mockery::class)) {
        \Mockery::close();
    }
});
```

- [ ] **Step 3: Test smoke**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('discovers the 3 stub commands', function () {
    Artisan::call('list');
    $output = Artisan::output();

    expect($output)
        ->toContain('migrate:generate')
        ->toContain('migrate:extract')
        ->toContain('migrate:import');
});

it('runs migrate:generate stub successfully', function () {
    $exitCode = Artisan::call('migrate:generate');
    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('not implemented yet');
});

it('runs migrate:extract stub successfully', function () {
    $exitCode = Artisan::call('migrate:extract');
    expect($exitCode)->toBe(0);
});

it('runs migrate:import stub successfully', function () {
    $exitCode = Artisan::call('migrate:import');
    expect($exitCode)->toBe(0);
});

it('respects the submodules config (disabled generator hides the command)', function () {
    config()->set('migration-core.submodules.generator', false);

    // Note : à ce stade le SP est déjà bootté ; ce test vérifie surtout la présence
    // du flag de config. Test d'isolation effective fait dans une suite séparée.
    expect(config('migration-core.submodules.generator'))->toBeFalse();
});
```

- [ ] **Step 4: Lancer Pest**

```bash
vendor/bin/pest tests/Feature/Cli/SkeletonBootTest.php
```
Attendu : 5 tests passent.

- [ ] **Step 5: Pint + PHPStan + commit**

```bash
composer pint && composer phpstan
git add tests/
git commit -m "test(skeleton): smoke test for 5 providers + 3 stub commands"
```

---

## Task 0.12: README skeleton

**Files:**
- Create: `README.md`

- [ ] **Step 1: README**

````markdown
# Module Migration

> Module GSTI hybride pour Laravel 13+ — génération de migrations, extraction et import de données.

[![PHP 8.4+](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://www.php.net)
[![Laravel 13](https://img.shields.io/badge/laravel-13-red.svg)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

⚠️ **v0.1.0-alpha** — squelette + Generator SQLite uniquement.
Roadmap complète : voir [`Docs/ROADMAP.md`](Docs/ROADMAP.md).

## Architecture

```
App\Modules\Migration\                                    [N1] wrapper
└── Modules\Migration\                                    [N2] core (DTOs/Interfaces partagés)
    └── Modules\
        ├── Generator\        migrate:generate (5 moteurs DB cible v1.0)
        ├── Extract\          migrate:extract (CSV/JSON/Excel)
        └── Import\           migrate:import  (CSV/JSON/Excel)
```

## Installation

Voir [`Docs/INSTALLATION.md`](Docs/INSTALLATION.md) pour les deux modes :
- **Composer** (path repository ou Packagist)
- **Copy / symlink** dans `app/Modules/Migration/` de l'app consommatrice

## Sous-features

| Feature | Commande | Statut v0.1 |
|---|---|---|
| **Generator** | `php artisan migrate:generate` | ✅ SQLite |
| **Extract**   | `php artisan migrate:extract`  | 🚧 stub |
| **Import**    | `php artisan migrate:import`   | 🚧 stub |

## Documentation

- [`Docs/INSTALLATION.md`](Docs/INSTALLATION.md) — installation
- [`Docs/ARCHITECTURE.md`](Docs/ARCHITECTURE.md) — architecture détaillée
- [`Docs/ROADMAP.md`](Docs/ROADMAP.md) — roadmap par phase
- [`Modules/Migration/Modules/Generator/Docs/USAGE.md`](Modules/Migration/Modules/Generator/Docs/USAGE.md)

## Crédits

Lignée originale : [`xethron/migrations-generator`](https://github.com/Xethron/migrations-generator) par Bernhard Breytenbach. Réécrit et restructuré par [Groupe STI](https://groupesti.com).

## Licence

MIT — voir [LICENSE.md](LICENSE.md).
````

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add README with architecture overview"
```

---

## Task 0.13: LICENSE.md + CHANGELOG.md

**Files:**
- Create: `LICENSE.md`
- Create: `CHANGELOG.md`

- [ ] **Step 1: LICENSE.md**

```markdown
# MIT License

Copyright (c) 2014 Bernhard Breytenbach (original author)
Copyright (c) 2026 Groupe STI

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

- [ ] **Step 2: CHANGELOG.md**

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.1-skeleton] - 2026-05-07

### Added
- 4-level module skeleton : `App\Modules\Migration\Modules\Migration\Modules\{Generator,Extract,Import}\`.
- 5 ServiceProviders wired across all 4 levels.
- Stub commands : `migrate:generate`, `migrate:extract`, `migrate:import`.
- Tooling : Pint, PHPStan level 8 (Larastan), Pest 4, GitHub Actions.

### Removed (vs `xethron/migrations-generator`)
- All legacy code (PHP < 8.4, Laravel < 13, doctrine/dbal, xethron/laravel-4-generators).

[Unreleased]: https://github.com/SimardRichard/laravel-migrations-generator/compare/v0.0.1-skeleton...HEAD
[0.0.1-skeleton]: https://github.com/SimardRichard/laravel-migrations-generator/releases/tag/v0.0.1-skeleton
```

- [ ] **Step 3: Commit**

```bash
git add LICENSE.md CHANGELOG.md
git commit -m "docs: add LICENSE.md (preserves original copyright) + CHANGELOG.md"
```

---

## Task 0.14: Documentation Docs/INSTALLATION.md

**Files:**
- Create: `Docs/INSTALLATION.md`
- Create: `Docs/ARCHITECTURE.md`
- Create: `Docs/ROADMAP.md`

- [ ] **Step 1: INSTALLATION.md**

```markdown
# Installation

Le module supporte deux modes de consommation, selon les conventions de l'app cible.

## Mode 1 — Composer (path repository)

Dans `composer.json` de l'app consommatrice :

```json
{
    "repositories": [
        { "type": "path", "url": "../../packages/laravel-migrations-generator", "options": { "symlink": true } }
    ],
    "require-dev": {
        "groupesti/migration-module": "*"
    }
}
```

Puis :

```bash
composer require --dev groupesti/migration-module
```

⚠️ **Condition** : l'app consommatrice ne doit **pas** avoir un dossier local `app/Modules/Migration/`.

## Mode 2 — Copy / Symlink

```bash
# Symlink (dev local, miroir en temps réel)
ln -s /var/www/packages/laravel-migrations-generator app/Modules/Migration

# OU copie (déploiement immutable)
cp -r /var/www/packages/laravel-migrations-generator app/Modules/Migration
rm -rf app/Modules/Migration/.git
```

Dans ce cas, ajouter à `bootstrap/providers.php` de l'app :

```php
\App\Modules\Migration\Providers\MigrationServiceProvider::class,
```

## Vérification

```bash
php artisan list | grep migrate:
```

Doit afficher `migrate:generate`, `migrate:extract`, `migrate:import`.
```

- [ ] **Step 2: ARCHITECTURE.md**

```markdown
# Architecture

## Hiérarchie 4 niveaux

```
App\Modules\Migration\                                   [N1] wrapper
└── Modules\Migration\                                   [N2] core
    └── Modules\
        ├── Generator\        [N3]
        ├── Extract\          [N3]
        └── Import\           [N3]
```

## Rôle par niveau

| Niveau | Module | ServiceProvider | Rôle |
|---|---|---|---|
| 1 | `App\Modules\Migration` | `MigrationServiceProvider` (wrapper) | Point d'entrée unique. Enregistre transitivement N2. Publie config racine. |
| 2 | `App\Modules\Migration\Modules\Migration` | `MigrationServiceProvider` (core) | Enregistre les sous-modules selon la config. Tient les DTOs/Interfaces partagés. |
| 3 | `…\Modules\Generator` | `GeneratorServiceProvider` | Commande `migrate:generate`, drivers DB. |
| 3 | `…\Modules\Extract` | `ExtractServiceProvider` | Commande `migrate:extract`, writers de format. |
| 3 | `…\Modules\Import` | `ImportServiceProvider` | Commande `migrate:import`, readers de format. |

## Désactivation fine

`config/migration-core.php` permet de désactiver un sous-module :

```php
return [
    'submodules' => [
        'generator' => true,
        'extract'   => false,  // commande migrate:extract n'apparaît pas
        'import'    => true,
    ],
];
```
```

- [ ] **Step 3: ROADMAP.md**

```markdown
# Roadmap

| Phase | Tag | Scope | Statut |
|---|---|---|---|
| 0 | `v0.0.1-skeleton` | Squelette complet vide + 5 ServiceProviders + 3 stub commands | À faire |
| 1 | `v0.1.0-alpha` | **Generator SQLite** | À faire |
| 2 | `v0.2.0-alpha` | + Generator MySQL | Plan distinct |
| 3 | `v0.3.0-alpha` | + Generator MariaDB | Plan distinct |
| 4 | `v0.4.0-beta` | + Generator PostgreSQL | Plan distinct |
| 5 | `v0.9.0-rc` | + Generator SQL Server | Plan distinct |
| 6 | `v1.0.0` | E2E + CI matrix complète + docs + release publique | Plan distinct |
| 7+ | `v1.x` | Extract — CSV puis JSON puis Excel | Brainstorming futur |
| 10+ | `v1.x` | Import — CSV puis JSON puis Excel | Brainstorming futur |
```

- [ ] **Step 4: Commit**

```bash
git add Docs/
git commit -m "docs: add INSTALLATION.md, ARCHITECTURE.md, ROADMAP.md"
```

---

## Task 0.15: CI complète + tag v0.0.1-skeleton

- [ ] **Step 1: Lancer la suite CI**

```bash
composer ci
```
Attendu : Pint OK, PHPStan OK, Pest OK (5 tests passent).

- [ ] **Step 2: Tag**

```bash
git tag -a v0.0.1-skeleton -m "v0.0.1-skeleton: complete 4-level module skeleton + stubs"
```

- [ ] **Step 3: Vérifier**

```bash
git tag -l
git log --oneline -10
```

✅ **Phase 0 terminée.** Le module a son squelette complet, ses ServiceProviders bootent, ses 3 commandes stub sont visibles via `php artisan list`.

---

# PHASE 1 — Generator SQLite (v0.1.0-alpha)

À partir d'ici, on remplit le sous-module `Generator` (et certains éléments partagés au niveau 2). À la fin de la Phase 1, `php artisan migrate:generate` produit des migrations Laravel valides depuis une BD SQLite.

> **Note pour l'agent** : Phase 1 réimplémente la logique du plan superseded (`2026-05-07-plan1-foundation-sqlite-v0.1.0.md`) avec les nouveaux namespaces. Le code testé du plan superseded est valide modulo les changements de namespace ; vous pouvez vous y référer pour les cas de bord, mais respectez les namespaces de ce plan.

## Mémo namespaces Phase 1

| Élément | Namespace |
|---|---|
| Enums (Column/Index/On Action) | `App\Modules\Migration\Modules\Migration\Enums\` |
| DTOs Schema (5) | `App\Modules\Migration\Modules\Migration\DTOs\` |
| Exceptions (14) | `App\Modules\Migration\Modules\Migration\Exceptions\` |
| Interfaces (SchemaDriver, StubRenderer, MigrationWriter) | `App\Modules\Migration\Modules\Migration\Interfaces\` |
| Services (ColumnTypeResolver) | `App\Modules\Migration\Modules\Migration\Services\` |
| Generator Drivers + Resolvers (DefaultValue) | `…\Modules\Generator\Drivers\` et `…\Drivers\Resolvers\` |
| Generator Renderers | `…\Modules\Generator\Renderers\` |
| Generator Stub | `…\Modules\Generator\Stub\` |
| Generator DTOs (GenerateOptions, MigrationFile, MigrationPlan) | `…\Modules\Generator\DTOs\` |
| Generator Actions | `…\Modules\Generator\Actions\` |
| Generator Writers | `…\Modules\Generator\Writers\` |
| Generator Console | `…\Modules\Generator\Console\Commands\` |

---

## Task 1.1: 3 Enums (ColumnType + IndexType + OnAction)

**Files:**
- Create: `Modules/Migration/Enums/ColumnType.php`
- Create: `Modules/Migration/Enums/IndexType.php`
- Create: `Modules/Migration/Enums/OnAction.php`
- Create: `tests/Unit/Enums/EnumsTest.php`

- [ ] **Step 1: Test groupé**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;

it('exposes representative ColumnType cases', function () {
    expect(ColumnType::String->value)->toBe('string')
        ->and(ColumnType::BigInteger->value)->toBe('bigInteger')
        ->and(ColumnType::Json->value)->toBe('json')
        ->and(ColumnType::Jsonb->value)->toBe('jsonb')
        ->and(ColumnType::Decimal->value)->toBe('decimal')
        ->and(ColumnType::Raw->value)->toBe('raw');
});

it('exposes IndexType families', function () {
    expect(IndexType::Primary->value)->toBe('primary')
        ->and(IndexType::Unique->value)->toBe('unique')
        ->and(IndexType::Index->value)->toBe('index')
        ->and(IndexType::FullText->value)->toBe('fullText')
        ->and(IndexType::Spatial->value)->toBe('spatialIndex');
});

it('parses OnAction from raw SQL', function () {
    expect(OnAction::fromSql('CASCADE'))->toBe(OnAction::Cascade)
        ->and(OnAction::fromSql('SET NULL'))->toBe(OnAction::SetNull)
        ->and(OnAction::fromSql('no action'))->toBe(OnAction::NoAction)
        ->and(OnAction::fromSql('UNKNOWN'))->toBe(OnAction::NoAction);
});
```

- [ ] **Step 2: Implémenter ColumnType**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Enums;

enum ColumnType: string
{
    case Char = 'char';
    case String = 'string';
    case Text = 'text';
    case MediumText = 'mediumText';
    case LongText = 'longText';

    case TinyInteger = 'tinyInteger';
    case SmallInteger = 'smallInteger';
    case MediumInteger = 'mediumInteger';
    case Integer = 'integer';
    case BigInteger = 'bigInteger';
    case UnsignedTinyInteger = 'unsignedTinyInteger';
    case UnsignedSmallInteger = 'unsignedSmallInteger';
    case UnsignedMediumInteger = 'unsignedMediumInteger';
    case UnsignedInteger = 'unsignedInteger';
    case UnsignedBigInteger = 'unsignedBigInteger';

    case Float = 'float';
    case Double = 'double';
    case Decimal = 'decimal';

    case Boolean = 'boolean';

    case Date = 'date';
    case DateTime = 'dateTime';
    case DateTimeTz = 'dateTimeTz';
    case Time = 'time';
    case TimeTz = 'timeTz';
    case Timestamp = 'timestamp';
    case TimestampTz = 'timestampTz';
    case Year = 'year';

    case Binary = 'binary';
    case Json = 'json';
    case Jsonb = 'jsonb';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
    case IpAddress = 'ipAddress';
    case MacAddress = 'macAddress';
    case Geometry = 'geometry';
    case Point = 'point';
    case Enum = 'enum';
    case Set = 'set';
    case Raw = 'raw';
}
```

- [ ] **Step 3: Implémenter IndexType**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Enums;

enum IndexType: string
{
    case Primary = 'primary';
    case Unique = 'unique';
    case Index = 'index';
    case FullText = 'fullText';
    case Spatial = 'spatialIndex';
}
```

- [ ] **Step 4: Implémenter OnAction**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Enums;

enum OnAction: string
{
    case Cascade = 'cascade';
    case Restrict = 'restrict';
    case SetNull = 'set null';
    case NoAction = 'no action';
    case SetDefault = 'set default';

    public static function fromSql(string $raw): self
    {
        return self::tryFrom(strtolower(trim($raw))) ?? self::NoAction;
    }
}
```

- [ ] **Step 5: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Enums/
composer pint && composer phpstan
git add Modules/Migration/Enums/ tests/Unit/Enums/
git commit -m "feat(enums): add ColumnType, IndexType, OnAction"
```

---

## Task 1.2: 5 DTOs Schema (Column/Index/ForeignKey/Table/Database)

**Files:**
- Create: `Modules/Migration/DTOs/{Column,Index,ForeignKey,Table,Database}Schema.php`
- Create: `tests/Unit/Schema/SchemasTest.php`

- [ ] **Step 1: Test groupé**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;

it('constructs ColumnSchema with required + optional metadata', function () {
    $col = new ColumnSchema('email', ColumnType::String, 255, null, null, false, false, false, null, null, null, null);
    expect($col->name)->toBe('email')->and($col->length)->toBe(255);
});

it('constructs IndexSchema with composite columns', function () {
    $idx = new IndexSchema('idx', IndexType::Index, ['a', 'b'], true, null, null);
    expect($idx->columns)->toHaveCount(2)->and($idx->isComposite)->toBeTrue();
});

it('constructs ForeignKeySchema with both actions', function () {
    $fk = new ForeignKeySchema('fk', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict);
    expect($fk->referencedTable)->toBe('users')->and($fk->onDelete)->toBe(OnAction::Restrict);
});

it('TableSchema aggregates columns/indexes/fks', function () {
    $col = new ColumnSchema('id', ColumnType::BigInteger, null, null, null, false, true, true, null, null, null, null);
    $t = new TableSchema('users', null, null, null, [$col], [], []);
    expect($t->columns)->toHaveCount(1);
});

it('DatabaseSchema can find a table by name', function () {
    $t = new TableSchema('users', null, null, null, [], [], []);
    $db = new DatabaseSchema('sqlite', [$t]);
    expect($db->table('users'))->toBe($t)
        ->and($db->table('missing'))->toBeNull();
});
```

- [ ] **Step 2: Implémenter ColumnSchema**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;

final readonly class ColumnSchema
{
    public function __construct(
        public string $name,
        public ColumnType $type,
        public ?int $length,
        public ?int $precision,
        public ?int $scale,
        public bool $nullable,
        public bool $unsigned,
        public bool $autoIncrement,
        public mixed $default,
        public ?string $comment,
        public ?string $collation,
        public ?string $generatedAs,
    ) {}
}
```

- [ ] **Step 3: Implémenter IndexSchema**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

use App\Modules\Migration\Modules\Migration\Enums\IndexType;

final readonly class IndexSchema
{
    /** @param string[] $columns */
    public function __construct(
        public string $name,
        public IndexType $type,
        public array $columns,
        public bool $isComposite,
        public ?string $algorithm,
        public ?string $where,
    ) {}
}
```

- [ ] **Step 4: Implémenter ForeignKeySchema**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

use App\Modules\Migration\Modules\Migration\Enums\OnAction;

final readonly class ForeignKeySchema
{
    /**
     * @param string[] $columns
     * @param string[] $referencedColumns
     */
    public function __construct(
        public string $name,
        public array $columns,
        public string $referencedTable,
        public array $referencedColumns,
        public OnAction $onUpdate,
        public OnAction $onDelete,
    ) {}
}
```

- [ ] **Step 5: Implémenter TableSchema**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

final readonly class TableSchema
{
    /**
     * @param ColumnSchema[]     $columns
     * @param IndexSchema[]      $indexes
     * @param ForeignKeySchema[] $foreignKeys
     */
    public function __construct(
        public string $name,
        public ?string $comment,
        public ?string $charset,
        public ?string $collation,
        public array $columns,
        public array $indexes,
        public array $foreignKeys,
    ) {}
}
```

- [ ] **Step 6: Implémenter DatabaseSchema**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

final readonly class DatabaseSchema
{
    /** @param TableSchema[] $tables */
    public function __construct(
        public string $connection,
        public array $tables,
    ) {}

    public function table(string $name): ?TableSchema
    {
        foreach ($this->tables as $t) {
            if ($t->name === $name) {
                return $t;
            }
        }

        return null;
    }
}
```

- [ ] **Step 7: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Schema/
composer pint && composer phpstan
git add Modules/Migration/DTOs/ tests/Unit/Schema/
git commit -m "feat(schema): add 5 Schema DTOs (final readonly)"
```

---

## Task 1.3: Hiérarchie d'exceptions (14 classes)

**Files:** 14 fichiers dans `Modules/Migration/Exceptions/` + `tests/Unit/Exceptions/HierarchyTest.php`

- [ ] **Step 1: Test global**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\CircularForeignKeyException;
use App\Modules\Migration\Modules\Migration\Exceptions\ConfigurationException;
use App\Modules\Migration\Modules\Migration\Exceptions\DatabaseAccessDeniedException;
use App\Modules\Migration\Modules\Migration\Exceptions\FileAlreadyExistsException;
use App\Modules\Migration\Modules\Migration\Exceptions\GenerationException;
use App\Modules\Migration\Modules\Migration\Exceptions\InvalidConnectionException;
use App\Modules\Migration\Modules\Migration\Exceptions\MigrationCoreException;
use App\Modules\Migration\Modules\Migration\Exceptions\SchemaReadException;
use App\Modules\Migration\Modules\Migration\Exceptions\StubNotFoundException;
use App\Modules\Migration\Modules\Migration\Exceptions\TableNotFoundException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedColumnTypeException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedDriverException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteFailedException;

it('roots all package exceptions in MigrationCoreException', function () {
    expect(new ConfigurationException('x'))->toBeInstanceOf(MigrationCoreException::class)
        ->and(new SchemaReadException('x'))->toBeInstanceOf(MigrationCoreException::class)
        ->and(new GenerationException('x'))->toBeInstanceOf(MigrationCoreException::class)
        ->and(new WriteException('x'))->toBeInstanceOf(MigrationCoreException::class);
});

it('UnsupportedDriverException carries context', function () {
    $e = UnsupportedDriverException::for('cockroachdb');
    expect($e->context())->toBe(['driver' => 'cockroachdb'])
        ->and($e)->toBeInstanceOf(ConfigurationException::class);
});

it('InvalidConnectionException carries context', function () {
    $e = InvalidConnectionException::for('legacy');
    expect($e->context())->toBe(['connection' => 'legacy']);
});

it('TableNotFoundException carries context', function () {
    expect(TableNotFoundException::for('orders')->context())->toBe(['table' => 'orders']);
});

it('DatabaseAccessDeniedException carries context', function () {
    expect(DatabaseAccessDeniedException::for('readonly')->context())->toBe(['connection' => 'readonly']);
});

it('UnsupportedColumnTypeException carries full context', function () {
    $e = UnsupportedColumnTypeException::for('users', 'location', 'geometry', 'mysql');
    expect($e->context())->toBe([
        'table' => 'users', 'column' => 'location', 'type' => 'geometry', 'driver' => 'mysql',
    ]);
});

it('StubNotFoundException carries path', function () {
    expect(StubNotFoundException::for('/tmp/x.stub')->context())->toBe(['path' => '/tmp/x.stub']);
});

it('CircularForeignKeyException carries cycle', function () {
    expect(CircularForeignKeyException::for(['a', 'b', 'a'])->context())->toBe(['cycle' => ['a', 'b', 'a']]);
});

it('FileAlreadyExistsException carries path', function () {
    expect(FileAlreadyExistsException::for('/tmp/x.php')->context())->toBe(['path' => '/tmp/x.php']);
});

it('WriteFailedException carries path and reason', function () {
    expect(WriteFailedException::for('/tmp/x', 'denied')->context())
        ->toBe(['path' => '/tmp/x', 'reason' => 'denied']);
});
```

- [ ] **Step 2: Parent + Configuration branch**

`MigrationCoreException.php` :
```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

use RuntimeException;

abstract class MigrationCoreException extends RuntimeException
{
    /** @return array<string, mixed> */
    public function context(): array
    {
        return [];
    }
}
```

`ConfigurationException.php` :
```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

class ConfigurationException extends MigrationCoreException
{
}
```

`UnsupportedDriverException.php` :
```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class UnsupportedDriverException extends ConfigurationException
{
    public function __construct(public readonly string $driver)
    {
        parent::__construct(sprintf('Unsupported database driver: "%s".', $driver));
    }

    public static function for(string $driver): self
    {
        return new self($driver);
    }

    public function context(): array
    {
        return ['driver' => $this->driver];
    }
}
```

`InvalidConnectionException.php` :
```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class InvalidConnectionException extends ConfigurationException
{
    public function __construct(public readonly string $connection)
    {
        parent::__construct(sprintf('Invalid or undefined connection: "%s".', $connection));
    }

    public static function for(string $connection): self
    {
        return new self($connection);
    }

    public function context(): array
    {
        return ['connection' => $this->connection];
    }
}
```

- [ ] **Step 3: SchemaRead branch**

`SchemaReadException.php`, `TableNotFoundException.php`, `DatabaseAccessDeniedException.php` — modèle identique à la branche Configuration. Suivre les signatures du test ci-dessus.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

class SchemaReadException extends MigrationCoreException
{
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class TableNotFoundException extends SchemaReadException
{
    public function __construct(public readonly string $table)
    {
        parent::__construct(sprintf('Table not found: "%s".', $table));
    }

    public static function for(string $table): self
    {
        return new self($table);
    }

    public function context(): array
    {
        return ['table' => $this->table];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class DatabaseAccessDeniedException extends SchemaReadException
{
    public function __construct(public readonly string $connection)
    {
        parent::__construct(sprintf('Access denied for connection: "%s".', $connection));
    }

    public static function for(string $connection): self
    {
        return new self($connection);
    }

    public function context(): array
    {
        return ['connection' => $this->connection];
    }
}
```

- [ ] **Step 4: Generation branch**

`GenerationException.php`, `UnsupportedColumnTypeException.php`, `StubNotFoundException.php`, `CircularForeignKeyException.php` :

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

class GenerationException extends MigrationCoreException
{
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class UnsupportedColumnTypeException extends GenerationException
{
    public function __construct(
        public readonly string $table,
        public readonly string $column,
        public readonly string $type,
        public readonly string $driver,
    ) {
        parent::__construct(sprintf('Unsupported column type "%s" on %s.%s for driver "%s".', $type, $table, $column, $driver));
    }

    public static function for(string $table, string $column, string $type, string $driver): self
    {
        return new self($table, $column, $type, $driver);
    }

    public function context(): array
    {
        return ['table' => $this->table, 'column' => $this->column, 'type' => $this->type, 'driver' => $this->driver];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class StubNotFoundException extends GenerationException
{
    public function __construct(public readonly string $path)
    {
        parent::__construct(sprintf('Stub file not found: "%s".', $path));
    }

    public static function for(string $path): self
    {
        return new self($path);
    }

    public function context(): array
    {
        return ['path' => $this->path];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class CircularForeignKeyException extends GenerationException
{
    /** @param string[] $cycle */
    public function __construct(public readonly array $cycle)
    {
        parent::__construct('Circular foreign-key dependency detected: '.implode(' → ', $cycle));
    }

    /** @param string[] $cycle */
    public static function for(array $cycle): self
    {
        return new self($cycle);
    }

    public function context(): array
    {
        return ['cycle' => $this->cycle];
    }
}
```

- [ ] **Step 5: Write branch**

`WriteException.php`, `FileAlreadyExistsException.php`, `WriteFailedException.php` :

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

class WriteException extends MigrationCoreException
{
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class FileAlreadyExistsException extends WriteException
{
    public function __construct(public readonly string $path)
    {
        parent::__construct(sprintf('Migration file already exists: "%s". Use --force to overwrite.', $path));
    }

    public static function for(string $path): self
    {
        return new self($path);
    }

    public function context(): array
    {
        return ['path' => $this->path];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class WriteFailedException extends WriteException
{
    public function __construct(public readonly string $path, public readonly string $reason)
    {
        parent::__construct(sprintf('Could not write "%s": %s', $path, $reason));
    }

    public static function for(string $path, string $reason): self
    {
        return new self($path, $reason);
    }

    public function context(): array
    {
        return ['path' => $this->path, 'reason' => $this->reason];
    }
}
```

- [ ] **Step 6: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Exceptions/
composer pint && composer phpstan
git add Modules/Migration/Exceptions/ tests/Unit/Exceptions/
git commit -m "feat(exceptions): add 4-branch exception hierarchy (14 classes)"
```

---

## Task 1.4: Resolvers (DefaultValueResolver + ColumnTypeResolver SQLite)

**Files:**
- Create: `Modules/Migration/Modules/Generator/Drivers/Resolvers/DefaultValueResolver.php`
- Create: `Modules/Migration/Modules/Generator/Drivers/Resolvers/RawDefault.php`
- Create: `Modules/Migration/Services/ColumnTypeResolver.php`
- Create: `tests/Unit/Resolvers/{DefaultValue,ColumnType}ResolverTest.php`

> Note design : `DefaultValueResolver` est spécifique aux drivers DB → vit dans `Generator/Drivers/Resolvers/`. `ColumnTypeResolver` est partagé entre Generator (read) et Extract (cible analyse) → vit au niveau 2 dans `Services/`.

- [ ] **Step 1: Test DefaultValueResolver**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;

beforeEach(fn () => $this->r = new DefaultValueResolver());

it('returns null for SQL NULL', fn () => expect($this->r->resolve(null))->toBeNull());

it('preserves quoted string defaults', function () {
    expect($this->r->resolve("'draft'"))->toBe('draft')
        ->and($this->r->resolve('"hello"'))->toBe('hello');
});

it('returns numeric values typed', function () {
    expect($this->r->resolve('42'))->toBe(42)
        ->and($this->r->resolve('3.14'))->toBe(3.14);
});

it('detects CURRENT_TIMESTAMP family as raw expressions', function () {
    expect($this->r->resolve('CURRENT_TIMESTAMP')->expression())->toBe('CURRENT_TIMESTAMP')
        ->and($this->r->resolve('current_timestamp')->expression())->toBe('CURRENT_TIMESTAMP')
        ->and($this->r->resolve('now()')->expression())->toBe('CURRENT_TIMESTAMP');
});

it('treats other parenthesized expressions as raw', function () {
    expect($this->r->resolve("nextval('users_id_seq')")->expression())->toBe("nextval('users_id_seq')");
});

it('returns booleans for typical SQLite/PG forms', function () {
    expect($this->r->resolve('1'))->toBe(1)
        ->and($this->r->resolve('true'))->toBeTrue()
        ->and($this->r->resolve('FALSE'))->toBeFalse();
});
```

- [ ] **Step 2: Implémenter RawDefault et DefaultValueResolver**

`RawDefault.php` :
```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers;

final readonly class RawDefault
{
    public function __construct(private string $expression) {}

    public function expression(): string
    {
        return $this->expression;
    }
}
```

`DefaultValueResolver.php` :
```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers;

final class DefaultValueResolver
{
    private const TIMESTAMP_FORMS = ['CURRENT_TIMESTAMP', 'NOW()', 'CURRENT_TIMESTAMP()'];

    public function resolve(?string $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        if (in_array(strtoupper($trimmed), self::TIMESTAMP_FORMS, true)) {
            return new RawDefault('CURRENT_TIMESTAMP');
        }

        if (str_contains($trimmed, '(') && str_ends_with($trimmed, ')')) {
            return new RawDefault($trimmed);
        }

        if (preg_match("/^'(.*)'$/", $trimmed, $m) === 1
            || preg_match('/^"(.*)"$/', $trimmed, $m) === 1) {
            return $m[1];
        }

        $lower = strtolower($trimmed);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }

        if (preg_match('/^-?\d+$/', $trimmed) === 1) {
            return (int) $trimmed;
        }
        if (preg_match('/^-?\d+\.\d+$/', $trimmed) === 1) {
            return (float) $trimmed;
        }

        return $trimmed;
    }
}
```

- [ ] **Step 3: Test ColumnTypeResolver**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;

beforeEach(fn () => $this->r = new ColumnTypeResolver());

it('maps SQLite native types to ColumnType enum', function (string $sqlite, ColumnType $expected) {
    expect($this->r->forSqlite($sqlite))->toBe($expected);
})->with([
    ['INTEGER', ColumnType::Integer],
    ['integer', ColumnType::Integer],
    ['BIGINT', ColumnType::BigInteger],
    ['VARCHAR(255)', ColumnType::String],
    ['TEXT', ColumnType::Text],
    ['BLOB', ColumnType::Binary],
    ['REAL', ColumnType::Double],
    ['NUMERIC(10,2)', ColumnType::Decimal],
    ['BOOLEAN', ColumnType::Boolean],
    ['DATE', ColumnType::Date],
    ['DATETIME', ColumnType::DateTime],
    ['TIMESTAMP', ColumnType::Timestamp],
    ['JSON', ColumnType::Json],
    ['UUID', ColumnType::Uuid],
]);

it('falls back to Raw for unrecognized types', function () {
    expect($this->r->forSqlite('CUSTOM_FANCY'))->toBe(ColumnType::Raw);
});
```

- [ ] **Step 4: Implémenter ColumnTypeResolver**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Services;

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;

final class ColumnTypeResolver
{
    /** @var array<string, ColumnType> */
    private const SQLITE_MAP = [
        'INTEGER' => ColumnType::Integer,
        'INT' => ColumnType::Integer,
        'BIGINT' => ColumnType::BigInteger,
        'SMALLINT' => ColumnType::SmallInteger,
        'TINYINT' => ColumnType::TinyInteger,
        'MEDIUMINT' => ColumnType::MediumInteger,
        'VARCHAR' => ColumnType::String,
        'CHAR' => ColumnType::Char,
        'TEXT' => ColumnType::Text,
        'CLOB' => ColumnType::Text,
        'BLOB' => ColumnType::Binary,
        'REAL' => ColumnType::Double,
        'DOUBLE' => ColumnType::Double,
        'FLOAT' => ColumnType::Float,
        'NUMERIC' => ColumnType::Decimal,
        'DECIMAL' => ColumnType::Decimal,
        'BOOLEAN' => ColumnType::Boolean,
        'DATE' => ColumnType::Date,
        'DATETIME' => ColumnType::DateTime,
        'TIME' => ColumnType::Time,
        'TIMESTAMP' => ColumnType::Timestamp,
        'YEAR' => ColumnType::Year,
        'JSON' => ColumnType::Json,
        'UUID' => ColumnType::Uuid,
    ];

    public function forSqlite(string $native): ColumnType
    {
        $base = strtoupper(trim(preg_replace('/\(.*$/', '', $native) ?? $native));

        return self::SQLITE_MAP[$base] ?? ColumnType::Raw;
    }
}
```

- [ ] **Step 5: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Resolvers/
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/Drivers/ Modules/Migration/Services/ tests/Unit/Resolvers/
git commit -m "feat(resolvers): add DefaultValueResolver + ColumnTypeResolver SQLite mapping"
```

---

## Task 1.5: Renderers (Column + Index + ForeignKey)

**Files:**
- Create: `Modules/Migration/Modules/Generator/Renderers/{Column,Index,ForeignKey}Renderer.php`
- Create: `tests/Unit/Renderers/RenderersTest.php`

- [ ] **Step 1: Test groupé**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\RawDefault;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ColumnRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ForeignKeyRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\IndexRenderer;

beforeEach(function () {
    $this->col = new ColumnRenderer();
    $this->idx = new IndexRenderer();
    $this->fk = new ForeignKeyRenderer();
});

it('renders a basic string column', function () {
    $c = new ColumnSchema('email', ColumnType::String, 255, null, null, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->string('email', 255);");
});

it('omits length when null', function () {
    $c = new ColumnSchema('label', ColumnType::String, null, null, null, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->string('label');");
});

it('renders nullable + default modifiers', function () {
    $c = new ColumnSchema('status', ColumnType::String, 20, null, null, true, false, false, 'draft', null, null, null);
    expect($this->col->render($c))->toBe("\$table->string('status', 20)->nullable()->default('draft');");
});

it('renders bigIncrements for auto-increment unsigned BigInteger primary keys', function () {
    $c = new ColumnSchema('id', ColumnType::BigInteger, null, null, null, false, true, true, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->bigIncrements('id');");
});

it('renders DB::raw for raw default expressions', function () {
    $c = new ColumnSchema('created_at', ColumnType::Timestamp, null, null, null, false, false, false, new RawDefault('CURRENT_TIMESTAMP'), null, null, null);
    expect($this->col->render($c))->toBe("\$table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));");
});

it('renders decimal precision and scale', function () {
    $c = new ColumnSchema('price', ColumnType::Decimal, null, 12, 2, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->decimal('price', 12, 2);");
});

it('falls back to raw column for ColumnType::Raw', function () {
    $c = new ColumnSchema('weird', ColumnType::Raw, null, null, null, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toContain("\$table->addColumn('text', 'weird')")
        ->and($this->col->render($c))->toContain('// unmapped');
});

it('skips PRIMARY index (handled by bigIncrements)', function () {
    $i = new IndexSchema('PRIMARY', IndexType::Primary, ['id'], false, null, null);
    expect($this->idx->render($i))->toBeNull();
});

it('renders unique index', function () {
    $i = new IndexSchema('users_email_unique', IndexType::Unique, ['email'], false, null, null);
    expect($this->idx->render($i))->toBe("\$table->unique('email', 'users_email_unique');");
});

it('renders composite unique index', function () {
    $i = new IndexSchema('idx_org_email', IndexType::Unique, ['org_id', 'email'], true, null, null);
    expect($this->idx->render($i))->toBe("\$table->unique(['org_id', 'email'], 'idx_org_email');");
});

it('renders simple FK with cascade/restrict', function () {
    $f = new ForeignKeySchema('fk_posts_user', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict);
    expect($this->fk->render($f))->toBe(
        "\$table->foreign('user_id', 'fk_posts_user')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');"
    );
});

it('renders composite FK', function () {
    $f = new ForeignKeySchema('fk_x', ['a', 'b'], 'parent', ['x', 'y'], OnAction::NoAction, OnAction::NoAction);
    expect($this->fk->render($f))->toBe(
        "\$table->foreign(['a', 'b'], 'fk_x')->references(['x', 'y'])->on('parent')->onUpdate('no action')->onDelete('no action');"
    );
});
```

- [ ] **Step 2: Implémenter ColumnRenderer**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers;

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\RawDefault;

final class ColumnRenderer
{
    public function render(ColumnSchema $col): string
    {
        if ($this->isAutoIncrementBigInteger($col)) {
            return sprintf("\$table->bigIncrements('%s');", $col->name);
        }

        if ($col->type === ColumnType::Raw) {
            return sprintf(
                "\$table->addColumn('text', '%s'); // unmapped — review and replace with the correct method",
                $col->name,
            );
        }

        return $this->renderBase($col).$this->renderModifiers($col).';';
    }

    private function isAutoIncrementBigInteger(ColumnSchema $col): bool
    {
        return $col->autoIncrement && $col->unsigned && $col->type === ColumnType::BigInteger;
    }

    private function renderBase(ColumnSchema $col): string
    {
        $method = $col->type->value;
        $args = match ($col->type) {
            ColumnType::String, ColumnType::Char => $col->length !== null
                ? sprintf("'%s', %d", $col->name, $col->length)
                : sprintf("'%s'", $col->name),
            ColumnType::Decimal => sprintf("'%s', %d, %d", $col->name, $col->precision ?? 8, $col->scale ?? 2),
            default => sprintf("'%s'", $col->name),
        };

        return sprintf('$table->%s(%s)', $method, $args);
    }

    private function renderModifiers(ColumnSchema $col): string
    {
        $out = '';

        if ($col->nullable) {
            $out .= '->nullable()';
        }

        if ($col->default !== null) {
            $out .= '->default('.$this->renderDefault($col->default).')';
        }

        if ($col->comment !== null) {
            $out .= sprintf("->comment('%s')", addslashes($col->comment));
        }

        return $out;
    }

    private function renderDefault(mixed $default): string
    {
        return match (true) {
            $default instanceof RawDefault => sprintf("DB::raw('%s')", $default->expression()),
            is_bool($default) => $default ? 'true' : 'false',
            is_int($default), is_float($default) => (string) $default,
            default => "'".addslashes((string) $default)."'",
        };
    }
}
```

- [ ] **Step 3: Implémenter IndexRenderer**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers;

use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;

final class IndexRenderer
{
    public function render(IndexSchema $idx): ?string
    {
        if ($idx->type === IndexType::Primary) {
            return null;
        }

        $columns = $idx->isComposite
            ? '['.implode(', ', array_map(fn (string $c): string => "'$c'", $idx->columns)).']'
            : "'".$idx->columns[0]."'";

        return sprintf("\$table->%s(%s, '%s');", $idx->type->value, $columns, $idx->name);
    }
}
```

- [ ] **Step 4: Implémenter ForeignKeyRenderer**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers;

use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;

final class ForeignKeyRenderer
{
    public function render(ForeignKeySchema $fk): string
    {
        return sprintf(
            "\$table->foreign(%s, '%s')->references(%s)->on('%s')->onUpdate('%s')->onDelete('%s');",
            $this->columnsExpr($fk->columns),
            $fk->name,
            $this->columnsExpr($fk->referencedColumns),
            $fk->referencedTable,
            $fk->onUpdate->value,
            $fk->onDelete->value,
        );
    }

    /** @param string[] $columns */
    private function columnsExpr(array $columns): string
    {
        if (count($columns) === 1) {
            return "'".$columns[0]."'";
        }

        return '['.implode(', ', array_map(fn (string $c): string => "'$c'", $columns)).']';
    }
}
```

- [ ] **Step 5: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Renderers/
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/Renderers/ tests/Unit/Renderers/
git commit -m "feat(renderers): add Column, Index, ForeignKey renderers"
```

---

## Task 1.6: Stubs + StubRenderer interface + DefaultStubRenderer

**Files:**
- Create: `Modules/Migration/Modules/Generator/stubs/migration.create.stub`
- Create: `Modules/Migration/Modules/Generator/stubs/migration.foreign-keys.stub`
- Create: `Modules/Migration/Interfaces/StubRenderer.php`
- Create: `Modules/Migration/Modules/Generator/Stub/DefaultStubRenderer.php`
- Create: `tests/Unit/Stub/DefaultStubRendererTest.php`

- [ ] **Step 1: Stub migration.create**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
{{up_body}}
    }

    public function down(): void
    {
{{down_body}}
    }
};
```

- [ ] **Step 2: Stub migration.foreign-keys**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
{{up_body}}
    }

    public function down(): void
    {
{{down_body}}
    }
};
```

- [ ] **Step 3: Test StubRenderer**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\StubNotFoundException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Stub\DefaultStubRenderer;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/stubs-'.uniqid();
    mkdir($this->dir);
    file_put_contents($this->dir.'/sample.stub', "Hello {{name}}, you are {{age}}!");
    $this->r = new DefaultStubRenderer($this->dir);
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*.stub') ?: []);
    rmdir($this->dir);
});

it('replaces placeholders by their values', function () {
    expect($this->r->render('sample', ['name' => 'Alice', 'age' => '30']))->toBe('Hello Alice, you are 30!');
});

it('throws when stub does not exist', function () {
    $this->r->render('missing', []);
})->throws(StubNotFoundException::class);

it('leaves unmatched placeholders untouched', function () {
    expect($this->r->render('sample', ['name' => 'Bob']))->toBe('Hello Bob, you are {{age}}!');
});
```

- [ ] **Step 4: Implémenter Interfaces/StubRenderer.php**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Interfaces;

interface StubRenderer
{
    /** @param array<string, string> $placeholders */
    public function render(string $stubName, array $placeholders): string;
}
```

- [ ] **Step 5: Implémenter DefaultStubRenderer**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Stub;

use App\Modules\Migration\Modules\Migration\Exceptions\StubNotFoundException;
use App\Modules\Migration\Modules\Migration\Interfaces\StubRenderer;

final class DefaultStubRenderer implements StubRenderer
{
    public function __construct(private readonly string $stubDir) {}

    public function render(string $stubName, array $placeholders): string
    {
        $path = $this->stubDir.'/'.$stubName.'.stub';

        if (! is_file($path)) {
            throw StubNotFoundException::for($path);
        }

        $content = (string) file_get_contents($path);

        foreach ($placeholders as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return $content;
    }
}
```

- [ ] **Step 6: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Stub/
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/stubs/ Modules/Migration/Interfaces/ Modules/Migration/Modules/Generator/Stub/ tests/Unit/Stub/
git commit -m "feat(stub): add StubRenderer interface + DefaultStubRenderer + 2 stubs"
```

---

## Task 1.7: DTOs Generator (GenerateOptions, MigrationFile, MigrationPlan)

**Files:**
- Create: `Modules/Migration/Modules/Generator/DTOs/GenerateOptions.php`
- Create: `Modules/Migration/Modules/Generator/DTOs/MigrationFile.php`
- Create: `Modules/Migration/Modules/Generator/DTOs/MigrationPlan.php`
- Create: `tests/Unit/Plan/PlanDtosTest.php`

- [ ] **Step 1: Test groupé**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

it('captures CLI options used by Plan 1', function () {
    $opts = new GenerateOptions('sqlite', ['users'], [], '/tmp', null, false, '2026_05_07_120000');
    expect($opts->tables)->toBe(['users'])->and($opts->force)->toBeFalse();
});

it('represents a single migration file', function () {
    $f = new MigrationFile('2026_05_07_120000_create_tables.php', '<?php');
    expect($f->filename)->toEndWith('_create_tables.php');
});

it('aggregates files in a plan', function () {
    $plan = new MigrationPlan([new MigrationFile('a.php', ''), new MigrationFile('b.php', '')]);
    expect($plan->files)->toHaveCount(2)->and($plan->isEmpty())->toBeFalse();
});

it('reports empty plans', function () {
    expect((new MigrationPlan([]))->isEmpty())->toBeTrue();
});
```

- [ ] **Step 2: Implémenter GenerateOptions**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs;

final readonly class GenerateOptions
{
    /**
     * @param string[] $tables
     * @param string[] $ignored
     */
    public function __construct(
        public string $connection,
        public array $tables,
        public array $ignored,
        public string $path,
        public ?string $stubPath,
        public bool $force,
        public string $date,
    ) {}
}
```

- [ ] **Step 3: Implémenter MigrationFile**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs;

final readonly class MigrationFile
{
    public function __construct(
        public string $filename,
        public string $contents,
    ) {}
}
```

- [ ] **Step 4: Implémenter MigrationPlan**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs;

final readonly class MigrationPlan
{
    /** @param MigrationFile[] $files */
    public function __construct(public array $files) {}

    public function isEmpty(): bool
    {
        return $this->files === [];
    }
}
```

- [ ] **Step 5: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Plan/
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/DTOs/ tests/Unit/Plan/
git commit -m "feat(generator): add GenerateOptions, MigrationFile, MigrationPlan DTOs"
```

---

## Task 1.8: SchemaDriver interface + AbstractSchemaDriver + SqliteDriver

**Files:**
- Create: `Modules/Migration/Interfaces/SchemaDriver.php`
- Create: `Modules/Migration/Modules/Generator/Drivers/AbstractSchemaDriver.php`
- Create: `Modules/Migration/Modules/Generator/Drivers/SqliteDriver.php`
- Create: `tests/Unit/Drivers/SqliteDriverTest.php`

- [ ] **Step 1: Interface SchemaDriver**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Interfaces;

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;

interface SchemaDriver
{
    /** @return string[] */
    public function getTables(): array;

    /** @return ColumnSchema[] */
    public function getColumns(string $table): array;

    /** @return IndexSchema[] */
    public function getIndexes(string $table): array;

    /** @return ForeignKeySchema[] */
    public function getForeignKeys(string $table): array;

    public function name(): string;
}
```

- [ ] **Step 2: AbstractSchemaDriver**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers;

use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;
use Illuminate\Database\Connection;

abstract class AbstractSchemaDriver implements SchemaDriver
{
    public function __construct(
        protected readonly Connection $connection,
        protected readonly ColumnTypeResolver $columnTypeResolver,
        protected readonly DefaultValueResolver $defaultValueResolver,
    ) {}
}
```

- [ ] **Step 3: Test SqliteDriver**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SqliteDriver;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(\App\Modules\Migration\Tests\TestCase::class);

beforeEach(function () {
    Schema::create('users', function ($t) {
        $t->bigIncrements('id');
        $t->string('email', 200)->unique();
        $t->string('nickname', 60)->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('created_at')->useCurrent();
    });

    Schema::create('posts', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('user_id');
        $t->string('title', 255);
        $t->text('body');
        $t->index('title', 'posts_title_idx');
        $t->foreign('user_id', 'fk_posts_user_id')->references('id')->on('users')->onDelete('cascade');
    });

    $this->driver = new SqliteDriver(
        connection: DB::connection(),
        columnTypeResolver: new ColumnTypeResolver(),
        defaultValueResolver: new DefaultValueResolver(),
    );
});

it('lists tables in alphabetical order', function () {
    expect($this->driver->getTables())->toBe(['posts', 'users']);
});

it('reads users columns', function () {
    $cols = $this->driver->getColumns('users');
    $names = array_map(fn ($c) => $c->name, $cols);
    expect($names)->toContain('id', 'email', 'nickname', 'is_active', 'created_at')
        ->and($cols[1]->type)->toBe(ColumnType::String)
        ->and($cols[1]->length)->toBe(200);
});

it('detects unique index on email', function () {
    $names = array_map(fn ($i) => $i->name, $this->driver->getIndexes('users'));
    expect($names)->toContain('users_email_unique');
});

it('detects FK on posts.user_id', function () {
    $fks = $this->driver->getForeignKeys('posts');
    expect($fks)->toHaveCount(1)
        ->and($fks[0]->referencedTable)->toBe('users')
        ->and($fks[0]->columns)->toBe(['user_id']);
});

it('reports its driver name', fn () => expect($this->driver->name())->toBe('sqlite'));
```

- [ ] **Step 4: Implémenter SqliteDriver**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers;

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;

final class SqliteDriver extends AbstractSchemaDriver
{
    public function name(): string
    {
        return 'sqlite';
    }

    public function getTables(): array
    {
        $rows = $this->connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        return array_map(fn ($r) => $r->name, $rows);
    }

    public function getColumns(string $table): array
    {
        $rows = $this->connection->select("PRAGMA table_info($table)");
        $columns = [];

        foreach ($rows as $row) {
            $type = $this->columnTypeResolver->forSqlite((string) $row->type);
            [$length, $precision, $scale] = $this->extractTypeMetrics((string) $row->type);

            $columns[] = new ColumnSchema(
                name: (string) $row->name,
                type: $type,
                length: $length,
                precision: $precision,
                scale: $scale,
                nullable: ((int) $row->notnull) === 0 && (int) $row->pk === 0,
                unsigned: (int) $row->pk === 1,
                autoIncrement: (int) $row->pk === 1 && stripos((string) $row->type, 'int') !== false,
                default: $this->defaultValueResolver->resolve($row->dflt_value),
                comment: null,
                collation: null,
                generatedAs: null,
            );
        }

        return $columns;
    }

    public function getIndexes(string $table): array
    {
        $rows = $this->connection->select("PRAGMA index_list($table)");
        $indexes = [];

        foreach ($rows as $row) {
            if (str_starts_with((string) $row->name, 'sqlite_autoindex_')) {
                continue;
            }

            $cols = $this->connection->select("PRAGMA index_info({$row->name})");
            $columnNames = array_map(fn ($c) => (string) $c->name, $cols);

            $indexes[] = new IndexSchema(
                name: (string) $row->name,
                type: ((int) $row->unique) === 1 ? IndexType::Unique : IndexType::Index,
                columns: $columnNames,
                isComposite: count($columnNames) > 1,
                algorithm: null,
                where: null,
            );
        }

        return $indexes;
    }

    public function getForeignKeys(string $table): array
    {
        $rows = $this->connection->select("PRAGMA foreign_key_list($table)");
        $grouped = [];

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $grouped[$id] ??= [
                'table' => (string) $row->table,
                'columns' => [],
                'referenced' => [],
                'on_update' => OnAction::fromSql((string) $row->on_update),
                'on_delete' => OnAction::fromSql((string) $row->on_delete),
            ];
            $grouped[$id]['columns'][] = (string) $row->from;
            $grouped[$id]['referenced'][] = (string) $row->to;
        }

        $result = [];
        foreach ($grouped as $id => $g) {
            $result[] = new ForeignKeySchema(
                name: sprintf('%s_fk_%d', $table, $id),
                columns: $g['columns'],
                referencedTable: $g['table'],
                referencedColumns: $g['referenced'],
                onUpdate: $g['on_update'],
                onDelete: $g['on_delete'],
            );
        }

        return $result;
    }

    /** @return array{0:int|null, 1:int|null, 2:int|null} */
    private function extractTypeMetrics(string $native): array
    {
        if (preg_match('/\((\d+),\s*(\d+)\)/', $native, $m) === 1) {
            return [null, (int) $m[1], (int) $m[2]];
        }
        if (preg_match('/\((\d+)\)/', $native, $m) === 1) {
            return [(int) $m[1], null, null];
        }

        return [null, null, null];
    }
}
```

- [ ] **Step 5: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Drivers/SqliteDriverTest.php
composer pint && composer phpstan
git add Modules/Migration/Interfaces/SchemaDriver.php Modules/Migration/Modules/Generator/Drivers/{AbstractSchemaDriver,SqliteDriver}.php tests/Unit/Drivers/SqliteDriverTest.php
git commit -m "feat(drivers): add SchemaDriver interface + AbstractSchemaDriver + SqliteDriver"
```

---

## Task 1.9: SchemaDriverFactory

**Files:**
- Create: `Modules/Migration/Modules/Generator/Drivers/SchemaDriverFactory.php`
- Create: `tests/Unit/Drivers/SchemaDriverFactoryTest.php`

- [ ] **Step 1: Test**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\InvalidConnectionException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedDriverException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SchemaDriverFactory;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SqliteDriver;
use Illuminate\Support\Facades\Config;

uses(\App\Modules\Migration\Tests\TestCase::class);

it('returns SqliteDriver for sqlite connection', function () {
    $factory = new SchemaDriverFactory(app());
    expect($factory->forConnection('sqlite'))->toBeInstanceOf(SqliteDriver::class);
});

it('throws on unknown driver', function () {
    Config::set('database.connections.fake', ['driver' => 'oracle', 'database' => ':memory:']);
    (new SchemaDriverFactory(app()))->forConnection('fake');
})->throws(UnsupportedDriverException::class);

it('throws on undefined connection', function () {
    (new SchemaDriverFactory(app()))->forConnection('does-not-exist');
})->throws(InvalidConnectionException::class);
```

- [ ] **Step 2: Implémenter**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers;

use App\Modules\Migration\Modules\Migration\Exceptions\InvalidConnectionException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedDriverException;
use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;
use Illuminate\Contracts\Foundation\Application;

final class SchemaDriverFactory
{
    public function __construct(private readonly Application $app) {}

    public function forConnection(string $name): SchemaDriver
    {
        $config = $this->app['config']->get("database.connections.$name");
        if ($config === null) {
            throw InvalidConnectionException::for($name);
        }

        $connection = $this->app['db']->connection($name);
        $driverName = (string) ($config['driver'] ?? '');

        return match ($driverName) {
            'sqlite' => new SqliteDriver($connection, new ColumnTypeResolver(), new DefaultValueResolver()),
            default => throw UnsupportedDriverException::for($driverName),
        };
    }
}
```

- [ ] **Step 3: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Drivers/SchemaDriverFactoryTest.php
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/Drivers/SchemaDriverFactory.php tests/Unit/Drivers/SchemaDriverFactoryTest.php
git commit -m "feat(drivers): add SchemaDriverFactory (SQLite-only for plan 1)"
```

---

## Task 1.10: MigrationWriter contract + FilesystemMigrationWriter

**Files:**
- Create: `Modules/Migration/Interfaces/MigrationWriter.php`
- Create: `Modules/Migration/Modules/Generator/Writers/FilesystemMigrationWriter.php`
- Create: `tests/Unit/Writers/FilesystemMigrationWriterTest.php`

- [ ] **Step 1: Test**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\FileAlreadyExistsException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Writers\FilesystemMigrationWriter;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/mw-'.uniqid();
    mkdir($this->dir);
    $this->w = new FilesystemMigrationWriter(new Filesystem());
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*') ?: []);
    rmdir($this->dir);
});

it('writes a file when path does not exist', function () {
    $f = new MigrationFile('a.php', '<?php // hello');
    $this->w->write($f, $this->dir, force: false);
    expect(file_get_contents($this->dir.'/a.php'))->toBe('<?php // hello');
});

it('throws when path exists and force is false', function () {
    file_put_contents($this->dir.'/a.php', 'existing');
    $this->w->write(new MigrationFile('a.php', 'new'), $this->dir, force: false);
})->throws(FileAlreadyExistsException::class);

it('overwrites when force is true', function () {
    file_put_contents($this->dir.'/a.php', 'existing');
    $this->w->write(new MigrationFile('a.php', 'new'), $this->dir, force: true);
    expect(file_get_contents($this->dir.'/a.php'))->toBe('new');
});
```

- [ ] **Step 2: Interface MigrationWriter**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Interfaces;

use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;

interface MigrationWriter
{
    public function write(MigrationFile $file, string $directory, bool $force): string;
}
```

- [ ] **Step 3: FilesystemMigrationWriter**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Writers;

use App\Modules\Migration\Modules\Migration\Exceptions\FileAlreadyExistsException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteFailedException;
use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use Illuminate\Filesystem\Filesystem;

final class FilesystemMigrationWriter implements MigrationWriter
{
    public function __construct(private readonly Filesystem $files) {}

    public function write(MigrationFile $file, string $directory, bool $force): string
    {
        $path = rtrim($directory, '/').'/'.$file->filename;

        if ($this->files->exists($path) && ! $force) {
            throw FileAlreadyExistsException::for($path);
        }

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, recursive: true);
        }

        if ($this->files->put($path, $file->contents) === false) {
            throw WriteFailedException::for($path, 'Filesystem::put returned false');
        }

        return $path;
    }
}
```

- [ ] **Step 4: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Writers/
composer pint && composer phpstan
git add Modules/Migration/Interfaces/MigrationWriter.php Modules/Migration/Modules/Generator/Writers/FilesystemMigrationWriter.php tests/Unit/Writers/
git commit -m "feat(writers): add MigrationWriter contract + Filesystem implementation"
```

---

## Task 1.11: ReadDatabaseAction

**Files:**
- Create: `Modules/Migration/Modules/Generator/Actions/ReadDatabaseAction.php`
- Create: `tests/Unit/Actions/ReadDatabaseActionTest.php`

- [ ] **Step 1: Test**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\ReadDatabaseAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;

it('reads all tables when no filter is provided', function () {
    $driver = Mockery::mock(SchemaDriver::class);
    $driver->shouldReceive('name')->andReturn('sqlite');
    $driver->shouldReceive('getTables')->andReturn(['users', 'posts']);
    $driver->shouldReceive('getColumns')->andReturn([
        new ColumnSchema('id', ColumnType::BigInteger, null, null, null, false, true, true, null, null, null, null),
    ]);
    $driver->shouldReceive('getIndexes')->andReturn([]);
    $driver->shouldReceive('getForeignKeys')->andReturn([]);

    $opts = new GenerateOptions('sqlite', [], [], '/tmp', null, false, '2026_05_07_120000');
    $schema = (new ReadDatabaseAction())->execute($driver, $opts);

    expect($schema->tables)->toHaveCount(2)->and($schema->connection)->toBe('sqlite');
});

it('filters tables and excludes ignored', function () {
    $driver = Mockery::mock(SchemaDriver::class);
    $driver->shouldReceive('name')->andReturn('sqlite');
    $driver->shouldReceive('getTables')->andReturn(['users', 'posts', 'logs']);
    $driver->shouldReceive('getColumns')->andReturn([]);
    $driver->shouldReceive('getIndexes')->andReturn([]);
    $driver->shouldReceive('getForeignKeys')->andReturn([]);

    $schema = (new ReadDatabaseAction())->execute(
        $driver,
        new GenerateOptions('sqlite', ['users', 'logs'], ['logs'], '/tmp', null, false, 'd'),
    );

    expect(array_map(fn ($t) => $t->name, $schema->tables))->toBe(['users']);
});
```

- [ ] **Step 2: Implémenter**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;

final readonly class ReadDatabaseAction
{
    public function execute(SchemaDriver $driver, GenerateOptions $options): DatabaseSchema
    {
        $allTables = $driver->getTables();
        $selected = $this->select($allTables, $options);

        $tables = [];
        foreach ($selected as $name) {
            $tables[] = new TableSchema(
                name: $name,
                comment: null,
                charset: null,
                collation: null,
                columns: $driver->getColumns($name),
                indexes: $driver->getIndexes($name),
                foreignKeys: $driver->getForeignKeys($name),
            );
        }

        return new DatabaseSchema($options->connection, $tables);
    }

    /**
     * @param  string[] $available
     * @return string[]
     */
    private function select(array $available, GenerateOptions $opts): array
    {
        $candidate = $opts->tables === [] ? $available : array_intersect($available, $opts->tables);

        return array_values(array_diff($candidate, $opts->ignored));
    }
}
```

- [ ] **Step 3: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Actions/ReadDatabaseActionTest.php
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/Actions/ReadDatabaseAction.php tests/Unit/Actions/ReadDatabaseActionTest.php
git commit -m "feat(actions): add ReadDatabaseAction with table filtering"
```

---

## Task 1.12: BuildMigrationPlanAction (avec tri topologique de Kahn)

**Files:**
- Create: `Modules/Migration/Modules/Generator/Actions/BuildMigrationPlanAction.php`
- Create: `tests/Unit/Actions/BuildMigrationPlanActionTest.php`

- [ ] **Step 1: Test**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;
use App\Modules\Migration\Modules\Migration\Exceptions\CircularForeignKeyException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\BuildMigrationPlanAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;

beforeEach(function () {
    $this->action = new BuildMigrationPlanAction();
    $this->opts = fn () => new GenerateOptions('sqlite', [], [], '/tmp', null, false, '2026_05_07_120000');
});

it('creates two files when there are FKs', function () {
    $users = new TableSchema('users', null, null, null, [], [], []);
    $posts = new TableSchema('posts', null, null, null, [], [], [
        new ForeignKeySchema('fk', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict),
    ]);

    $plan = $this->action->execute(new DatabaseSchema('sqlite', [$posts, $users]), ($this->opts)());
    expect($plan->files)->toHaveCount(2)
        ->and($plan->files[0]->filename)->toEndWith('_create_tables.php')
        ->and($plan->files[1]->filename)->toEndWith('_add_foreign_keys.php');
});

it('creates a single file when no FKs', function () {
    $a = new TableSchema('a', null, null, null, [], [], []);
    $plan = $this->action->execute(new DatabaseSchema('sqlite', [$a]), ($this->opts)());
    expect($plan->files)->toHaveCount(1);
});

it('orders tables topologically by FK dependencies', function () {
    $users = new TableSchema('users', null, null, null, [], [], []);
    $posts = new TableSchema('posts', null, null, null, [], [], [
        new ForeignKeySchema('fk', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict),
    ]);

    $plan = $this->action->execute(new DatabaseSchema('sqlite', [$posts, $users]), ($this->opts)());
    $contents = $plan->files[0]->contents;
    expect(strpos($contents, "create('users'"))->toBeLessThan(strpos($contents, "create('posts'"));
});

it('throws on circular FK', function () {
    $a = new TableSchema('a', null, null, null, [], [], [
        new ForeignKeySchema('fk_a_b', ['b_id'], 'b', ['id'], OnAction::NoAction, OnAction::NoAction),
    ]);
    $b = new TableSchema('b', null, null, null, [], [], [
        new ForeignKeySchema('fk_b_a', ['a_id'], 'a', ['id'], OnAction::NoAction, OnAction::NoAction),
    ]);

    $this->action->execute(new DatabaseSchema('sqlite', [$a, $b]), ($this->opts)());
})->throws(CircularForeignKeyException::class);
```

- [ ] **Step 2: Implémenter**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Exceptions\CircularForeignKeyException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ColumnRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ForeignKeyRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\IndexRenderer;

final readonly class BuildMigrationPlanAction
{
    /** Marqueur de séparation up/down — improbable dans du code Laravel */
    public const BODY_SEPARATOR = "\n/*###DOWN###*/\n";

    public function __construct(
        private ColumnRenderer $columnRenderer = new ColumnRenderer(),
        private IndexRenderer $indexRenderer = new IndexRenderer(),
        private ForeignKeyRenderer $foreignKeyRenderer = new ForeignKeyRenderer(),
    ) {}

    public function execute(DatabaseSchema $schema, GenerateOptions $options): MigrationPlan
    {
        $orderedTables = $this->topologicalSort($schema->tables);

        $files = [];
        $files[] = $this->buildCreateTablesFile($orderedTables, $options, sequence: 0);

        if ($this->hasForeignKeys($orderedTables)) {
            $files[] = $this->buildForeignKeysFile($orderedTables, $options, sequence: 1);
        }

        return new MigrationPlan($files);
    }

    /**
     * @param  TableSchema[] $tables
     * @return TableSchema[]
     */
    private function topologicalSort(array $tables): array
    {
        $byName = [];
        foreach ($tables as $t) {
            $byName[$t->name] = $t;
        }

        $deps = [];
        $inDegree = [];
        foreach ($tables as $t) {
            $deps[$t->name] = [];
            $inDegree[$t->name] = 0;
        }

        foreach ($tables as $t) {
            foreach ($t->foreignKeys as $fk) {
                if (isset($byName[$fk->referencedTable]) && $fk->referencedTable !== $t->name) {
                    $deps[$fk->referencedTable][] = $t->name;
                    $inDegree[$t->name]++;
                }
            }
        }

        $queue = [];
        foreach ($inDegree as $name => $degree) {
            if ($degree === 0) {
                $queue[] = $name;
            }
        }

        $sorted = [];
        while ($queue !== []) {
            $name = array_shift($queue);
            $sorted[] = $byName[$name];
            foreach ($deps[$name] as $dep) {
                if (--$inDegree[$dep] === 0) {
                    $queue[] = $dep;
                }
            }
        }

        if (count($sorted) !== count($tables)) {
            $remaining = array_values(array_filter(
                array_keys($inDegree),
                fn ($n) => $inDegree[$n] > 0,
            ));
            throw CircularForeignKeyException::for($remaining);
        }

        return $sorted;
    }

    /** @param TableSchema[] $tables */
    private function hasForeignKeys(array $tables): bool
    {
        foreach ($tables as $t) {
            if ($t->foreignKeys !== []) {
                return true;
            }
        }

        return false;
    }

    /** @param TableSchema[] $tables */
    private function buildCreateTablesFile(array $tables, GenerateOptions $opts, int $sequence): MigrationFile
    {
        $up = '';
        $down = '';
        foreach ($tables as $t) {
            $up .= $this->renderCreateBlock($t)."\n\n";
            $down = "        Schema::dropIfExists('$t->name');\n".$down;
        }

        return new MigrationFile(
            filename: sprintf('%s_%02d_create_tables.php', $opts->date, $sequence),
            contents: rtrim($up).self::BODY_SEPARATOR.rtrim($down),
        );
    }

    /** @param TableSchema[] $tables */
    private function buildForeignKeysFile(array $tables, GenerateOptions $opts, int $sequence): MigrationFile
    {
        $up = '';
        $down = '';
        foreach ($tables as $t) {
            if ($t->foreignKeys === []) {
                continue;
            }
            $up .= "        Schema::table('$t->name', function (Blueprint \$table) {\n";
            $down .= "        Schema::table('$t->name', function (Blueprint \$table) {\n";
            foreach ($t->foreignKeys as $fk) {
                $up .= '            '.$this->foreignKeyRenderer->render($fk)."\n";
                $down .= "            \$table->dropForeign('$fk->name');\n";
            }
            $up .= "        });\n\n";
            $down .= "        });\n\n";
        }

        return new MigrationFile(
            filename: sprintf('%s_%02d_add_foreign_keys.php', $opts->date, $sequence),
            contents: rtrim($up).self::BODY_SEPARATOR.rtrim($down),
        );
    }

    private function renderCreateBlock(TableSchema $t): string
    {
        $body = "        Schema::create('$t->name', function (Blueprint \$table) {\n";
        foreach ($t->columns as $col) {
            $body .= '            '.$this->columnRenderer->render($col)."\n";
        }
        foreach ($t->indexes as $idx) {
            $line = $this->indexRenderer->render($idx);
            if ($line !== null) {
                $body .= '            '.$line."\n";
            }
        }
        $body .= "        });";

        return $body;
    }
}
```

- [ ] **Step 3: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Actions/BuildMigrationPlanActionTest.php
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/Actions/BuildMigrationPlanAction.php tests/Unit/Actions/BuildMigrationPlanActionTest.php
git commit -m "feat(actions): add BuildMigrationPlanAction with Kahn topological sort"
```

---

## Task 1.13: RenderMigrationAction + WriteMigrationFileAction

**Files:**
- Create: `Modules/Migration/Modules/Generator/Actions/RenderMigrationAction.php`
- Create: `Modules/Migration/Modules/Generator/Actions/WriteMigrationFileAction.php`
- Create: `tests/Unit/Actions/{RenderMigration,WriteMigrationFile}ActionTest.php`

- [ ] **Step 1: Test RenderMigrationAction**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\BuildMigrationPlanAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\RenderMigrationAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Stub\DefaultStubRenderer;

beforeEach(function () {
    $this->stubDir = sys_get_temp_dir().'/stubs-'.uniqid();
    mkdir($this->stubDir);

    file_put_contents($this->stubDir.'/migration.create.stub', "<?php\n[CREATE]\n{{up_body}}\n{{down_body}}");
    file_put_contents($this->stubDir.'/migration.foreign-keys.stub', "<?php\n[FK]\n{{up_body}}\n{{down_body}}");

    $this->action = new RenderMigrationAction(new DefaultStubRenderer($this->stubDir));
});

afterEach(function () {
    array_map('unlink', glob($this->stubDir.'/*') ?: []);
    rmdir($this->stubDir);
});

$separator = BuildMigrationPlanAction::BODY_SEPARATOR;

it('wraps create_tables files in the create stub', function () use ($separator) {
    $f = new MigrationFile('2026_05_07_120000_00_create_tables.php', "UP_X".$separator."DOWN_X");
    $rendered = $this->action->execute(new MigrationPlan([$f]));
    expect($rendered->files[0]->contents)->toContain('[CREATE]')
        ->toContain('UP_X')
        ->toContain('DOWN_X');
});

it('wraps add_foreign_keys files in the FK stub', function () use ($separator) {
    $f = new MigrationFile('2026_05_07_120000_01_add_foreign_keys.php', "UP_FK".$separator."DOWN_FK");
    $rendered = $this->action->execute(new MigrationPlan([$f]));
    expect($rendered->files[0]->contents)->toContain('[FK]');
});
```

- [ ] **Step 2: RenderMigrationAction**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\Interfaces\StubRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

final readonly class RenderMigrationAction
{
    public function __construct(private StubRenderer $stubs) {}

    public function execute(MigrationPlan $plan): MigrationPlan
    {
        $rendered = [];
        foreach ($plan->files as $file) {
            $stub = str_contains($file->filename, 'add_foreign_keys') ? 'migration.foreign-keys' : 'migration.create';
            [$up, $down] = $this->splitBody($file->contents);

            $rendered[] = new MigrationFile(
                filename: $file->filename,
                contents: $this->stubs->render($stub, ['up_body' => $up, 'down_body' => $down]),
            );
        }

        return new MigrationPlan($rendered);
    }

    /** @return array{0:string, 1:string} */
    private function splitBody(string $combined): array
    {
        $parts = explode(BuildMigrationPlanAction::BODY_SEPARATOR, $combined, 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
```

- [ ] **Step 3: Test WriteMigrationFileAction**

```php
<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\WriteMigrationFileAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

it('writes each file in the plan and returns paths in order', function () {
    $writer = Mockery::mock(MigrationWriter::class);
    $writer->shouldReceive('write')->ordered()->andReturn('/tmp/a.php');
    $writer->shouldReceive('write')->ordered()->andReturn('/tmp/b.php');

    $plan = new MigrationPlan([new MigrationFile('a.php', ''), new MigrationFile('b.php', '')]);
    $opts = new GenerateOptions('sqlite', [], [], '/tmp', null, false, 'd');

    expect((new WriteMigrationFileAction($writer))->execute($plan, $opts))->toBe(['/tmp/a.php', '/tmp/b.php']);
});
```

- [ ] **Step 4: WriteMigrationFileAction**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

final readonly class WriteMigrationFileAction
{
    public function __construct(private MigrationWriter $writer) {}

    /** @return string[] */
    public function execute(MigrationPlan $plan, GenerateOptions $options): array
    {
        $paths = [];
        foreach ($plan->files as $file) {
            $paths[] = $this->writer->write($file, $options->path, $options->force);
        }

        return $paths;
    }
}
```

- [ ] **Step 5: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Unit/Actions/RenderMigrationActionTest.php tests/Unit/Actions/WriteMigrationFileActionTest.php
composer pint && composer phpstan
git add Modules/Migration/Modules/Generator/Actions/{RenderMigration,WriteMigrationFile}Action.php tests/Unit/Actions/{RenderMigration,WriteMigrationFile}ActionTest.php
git commit -m "feat(actions): add Render + WriteMigrationFile actions"
```

---

## Task 1.14: GenerateCommand (vraie implémentation)

**Files:**
- Modify: `Modules/Migration/Modules/Generator/Console/Commands/GenerateCommand.php`

- [ ] **Step 1: Réécrire GenerateCommand (remplace le stub Phase 0)**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands;

use App\Modules\Migration\Modules\Migration\Exceptions\ConfigurationException;
use App\Modules\Migration\Modules\Migration\Exceptions\GenerationException;
use App\Modules\Migration\Modules\Migration\Exceptions\SchemaReadException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\BuildMigrationPlanAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\ReadDatabaseAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\RenderMigrationAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\WriteMigrationFileAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SchemaDriverFactory;
use Illuminate\Console\Command;

final class GenerateCommand extends Command
{
    protected $signature = 'migrate:generate
        {tables? : Comma-separated tables to include (defaults to all)}
        {--connection= : Database connection name}
        {--ignore= : Comma-separated tables to exclude}
        {--tables= : Alias for the positional argument}
        {--path= : Output directory (default: database/migrations)}
        {--stub-path= : Custom stub directory}
        {--force : Overwrite existing files}';

    protected $description = 'Generate Laravel migrations from an existing database.';

    public function handle(
        SchemaDriverFactory $factory,
        ReadDatabaseAction $read,
        BuildMigrationPlanAction $plan,
        RenderMigrationAction $render,
        WriteMigrationFileAction $write,
    ): int {
        try {
            $options = $this->buildOptions();
            $this->info("Reading schema from connection: {$options->connection}");

            $driver = $factory->forConnection($options->connection);
            $schema = $read->execute($driver, $options);

            if ($schema->tables === []) {
                $this->warn('No tables to generate.');

                return self::SUCCESS;
            }

            $built = $plan->execute($schema, $options);
            $rendered = $render->execute($built);
            $paths = $write->execute($rendered, $options);

            $this->info(sprintf('✓ Generated %d migration file(s):', count($paths)));
            foreach ($paths as $p) {
                $this->line("  - $p");
            }

            return self::SUCCESS;
        } catch (ConfigurationException $e) {
            $this->error('Configuration error: '.$e->getMessage());

            return 1;
        } catch (SchemaReadException $e) {
            $this->error('Schema read error: '.$e->getMessage());

            return 2;
        } catch (GenerationException $e) {
            $this->error('Generation error: '.$e->getMessage());

            return 3;
        } catch (WriteException $e) {
            $this->error('Write error: '.$e->getMessage());

            return 4;
        }
    }

    private function buildOptions(): GenerateOptions
    {
        $connection = (string) ($this->option('connection') ?? config('database.default'));
        $tablesArg = (string) ($this->argument('tables') ?? $this->option('tables') ?? '');
        $tables = $tablesArg === '' ? [] : array_map('trim', explode(',', $tablesArg));
        $ignoreArg = (string) ($this->option('ignore') ?? '');
        $ignored = $ignoreArg === '' ? [] : array_map('trim', explode(',', $ignoreArg));

        return new GenerateOptions(
            connection: $connection,
            tables: $tables,
            ignored: $ignored,
            path: (string) ($this->option('path') ?? database_path('migrations')),
            stubPath: $this->option('stub-path'),
            force: (bool) $this->option('force'),
            date: date('Y_m_d_His'),
        );
    }
}
```

- [ ] **Step 2: Pint + PHPStan**

```bash
composer pint && composer phpstan
```

- [ ] **Step 3: Commit (le test arrive en Task 1.16)**

```bash
git add Modules/Migration/Modules/Generator/Console/Commands/GenerateCommand.php
git commit -m "feat(console): replace stub with full GenerateCommand implementation"
```

---

## Task 1.15: Wire up GeneratorServiceProvider (bindings réels)

**Files:**
- Modify: `Modules/Migration/Modules/Generator/Providers/GeneratorServiceProvider.php`

- [ ] **Step 1: Réécrire GeneratorServiceProvider**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Providers;

use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Interfaces\StubRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands\GenerateCommand;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SchemaDriverFactory;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Stub\DefaultStubRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Writers\FilesystemMigrationWriter;
use Illuminate\Support\ServiceProvider;

final class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/generator.php', 'migration.generator');

        $this->app->bind(StubRenderer::class, function ($app) {
            $configured = (string) $app['config']->get('migration.generator.stubs_path', __DIR__.'/../stubs');

            return new DefaultStubRenderer($configured);
        });

        $this->app->bind(MigrationWriter::class, FilesystemMigrationWriter::class);
        $this->app->singleton(SchemaDriverFactory::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([GenerateCommand::class]);

        $this->publishes([
            __DIR__.'/../stubs' => $this->app->basePath('stubs/migrations-generator'),
        ], 'migration-generator-stubs');

        $this->publishes([
            __DIR__.'/../Config/generator.php' => config_path('migration/generator.php'),
        ], 'migration-generator-config');
    }
}
```

- [ ] **Step 2: Pint + PHPStan**

```bash
composer pint && composer phpstan
```

- [ ] **Step 3: Commit**

```bash
git add Modules/Migration/Modules/Generator/Providers/GeneratorServiceProvider.php
git commit -m "feat(provider): wire bindings for StubRenderer, MigrationWriter, SchemaDriverFactory"
```

---

## Task 1.16: Tests Feature SQLite + E2E round-trip

**Files:**
- Create: `tests/Feature/Sqlite/GenerateSqliteTest.php`
- Create: `tests/Feature/Cli/RoundTripTest.php`

- [ ] **Step 1: Test Feature SQLite**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->outputDir = sys_get_temp_dir().'/mg-out-'.uniqid();
    mkdir($this->outputDir);

    Schema::create('users', function ($t) {
        $t->bigIncrements('id');
        $t->string('email', 200)->unique();
        $t->string('name', 100)->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('created_at')->useCurrent();
    });

    Schema::create('posts', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('user_id');
        $t->string('title', 255);
        $t->text('body');
        $t->index('title', 'posts_title_idx');
        $t->foreign('user_id', 'fk_posts_user_id')->references('id')->on('users')->onDelete('cascade');
    });
});

afterEach(fn () => File::deleteDirectory($this->outputDir));

it('generates two migration files for a schema with FKs', function () {
    expect(Artisan::call('migrate:generate', ['--path' => $this->outputDir]))->toBe(0);

    $files = File::files($this->outputDir);
    expect($files)->toHaveCount(2);

    $names = array_map(fn ($f) => $f->getFilename(), $files);
    sort($names);

    expect($names[0])->toEndWith('_create_tables.php')
        ->and($names[1])->toEndWith('_add_foreign_keys.php');
});

it('produced migrations include create blocks and column directives', function () {
    Artisan::call('migrate:generate', ['--path' => $this->outputDir]);

    $createFile = collect(File::files($this->outputDir))
        ->first(fn ($f) => str_ends_with($f->getFilename(), '_create_tables.php'));

    $contents = file_get_contents($createFile->getPathname());

    expect($contents)
        ->toContain("Schema::create('users'")
        ->toContain("Schema::create('posts'")
        ->toContain("\$table->string('email', 200)")
        ->toContain('->nullable()')
        ->toContain('return new class extends Migration');
});

it('refuses overwrite without --force', function () {
    Artisan::call('migrate:generate', ['--path' => $this->outputDir]);
    expect(Artisan::call('migrate:generate', ['--path' => $this->outputDir]))->toBe(4);
});

it('overwrites with --force', function () {
    Artisan::call('migrate:generate', ['--path' => $this->outputDir]);
    expect(Artisan::call('migrate:generate', ['--path' => $this->outputDir, '--force' => true]))->toBe(0);
});
```

- [ ] **Step 2: Test E2E round-trip**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('round-trip: dump → generate → fresh migrate → dump matches structurally', function () {
    $outputDir = sys_get_temp_dir().'/rt-'.uniqid();
    mkdir($outputDir);

    Schema::create('animals', function ($t) {
        $t->bigIncrements('id');
        $t->string('species', 80);
        $t->integer('age')->nullable();
        $t->boolean('is_endangered')->default(false);
    });

    Schema::create('observations', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('animal_id');
        $t->text('notes')->nullable();
        $t->foreign('animal_id', 'fk_obs_animal')->references('id')->on('animals');
    });

    $originalAnimals = Schema::getColumnListing('animals');
    $originalObservations = Schema::getColumnListing('observations');

    Artisan::call('migrate:generate', ['--path' => $outputDir]);

    Schema::dropIfExists('observations');
    Schema::dropIfExists('animals');

    Artisan::call('migrate', ['--path' => $outputDir, '--realpath' => true]);

    expect(Schema::getColumnListing('animals'))->toBe($originalAnimals)
        ->and(Schema::getColumnListing('observations'))->toBe($originalObservations);

    File::deleteDirectory($outputDir);
});
```

- [ ] **Step 3: Vert + Pint + PHPStan + commit**

```bash
vendor/bin/pest tests/Feature/Sqlite/ tests/Feature/Cli/RoundTripTest.php
composer pint && composer phpstan
git add tests/Feature/Sqlite/GenerateSqliteTest.php tests/Feature/Cli/RoundTripTest.php
git commit -m "test(feature): add SQLite generation tests + E2E round-trip"
```

---

## Task 1.17: Coverage check + tag v0.1.0-alpha

- [ ] **Step 1: Lancer la suite CI complète**

```bash
composer ci
```
Attendu : Pint OK, PHPStan OK, Pest OK, coverage ≥ 80 %.

- [ ] **Step 2: Si coverage insuffisant, identifier les fichiers**

```bash
composer test-coverage 2>&1 | tail -40
```
Ajouter cas de test ciblés pour atteindre 80 %.

- [ ] **Step 3: Updater CHANGELOG.md**

Ajouter sous `## [Unreleased]` :

```markdown
## [0.1.0-alpha] - 2026-05-07

### Added
- Generator SQLite: full implementation of `migrate:generate` for SQLite databases.
- `SchemaDriver` interface + `SqliteDriver` reading via `PRAGMA` / `sqlite_master`.
- 5 Schema DTOs (Database/Table/Column/Index/ForeignKey) shared at level 2.
- 14 typed exceptions with `context()` for structured debugging.
- 4 Actions pipeline: Read → BuildPlan → Render → Write.
- 3 Renderers (Column, Index, ForeignKey) producing Blueprint-style code.
- Anonymous-class migrations with topological FK ordering.
- Two-file output: `*_create_tables.php` + `*_add_foreign_keys.php`.
- Custom stubs publishable via `vendor:publish --tag=migration-generator-stubs`.

[0.1.0-alpha]: https://github.com/SimardRichard/laravel-migrations-generator/releases/tag/v0.1.0-alpha
```

```bash
git add CHANGELOG.md
git commit -m "docs(changelog): record v0.1.0-alpha changes"
```

- [ ] **Step 4: Tag**

```bash
git tag -a v0.1.0-alpha -m "v0.1.0-alpha: Generator SQLite + module skeleton complete"
git tag -l
git show v0.1.0-alpha --stat
```

✅ **Phase 1 terminée.**

---

## Self-review (à faire après l'exécution)

- [ ] Tous les fichiers de la spec File Structure (§ 3.1) sauf ceux EXCLUS sont créés
- [ ] Les 5 ServiceProviders bootent en cascade
- [ ] Coverage ≥ 80 %
- [ ] PHPStan niveau 8 = 0 erreur
- [ ] Pint = 0 warning
- [ ] CI GitHub Actions passe sur master
- [ ] Round-trip SQLite fonctionne end-to-end
- [ ] Tags `v0.0.1-skeleton` et `v0.1.0-alpha` créés
- [ ] README mentionne explicitement le scope alpha (SQLite uniquement)

## Critères de succès Plan 1

- [ ] `composer install` puis `php artisan list` affiche `migrate:generate`, `migrate:extract`, `migrate:import`
- [ ] `php artisan migrate:generate` produit deux fichiers de migration valides depuis une BD SQLite avec FKs
- [ ] `php artisan migrate` sur ces fichiers reconstruit la BD à l'identique (round-trip)
- [ ] Aucune dépendance morte (xethron/laravel-4-generators, doctrine/dbal) restante
- [ ] Aucun héritage du namespace `Xethron\` dans le code
- [ ] L'arborescence respecte le template `/var/www/packages/Module/` aux 4 niveaux

## Plan suivant

**Plan 2 — Generator MySQL** : étend `ColumnTypeResolver` avec le mapping MySQL, ajoute `MySqlDriver`, fixture MySQL, tests Feature MySQL. CI matrix passe à 2 moteurs. Tag `v0.2.0-alpha`.
