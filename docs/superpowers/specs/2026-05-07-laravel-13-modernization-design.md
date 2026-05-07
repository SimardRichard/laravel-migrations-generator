# Modernisation Laravel 13+ — Design v1.0

| Champ | Valeur |
|---|---|
| **Date** | 2026-05-07 |
| **Statut** | Validé (brainstorming) |
| **Auteur** | Richard Simard (Groupe STI) |
| **Phase** | 1 / 2 (v1.0) |
| **Cible release** | v1.0.0 sur Packagist |

---

## 1. Contexte et vision

### 1.1 Point de départ

Le repo actuel est un fork de `xethron/migrations-generator`, lui-même issu de `SimardRichard/laravel-migrations-generator`. Le code est figé sur Laravel 5.5, PHP 5.4+, et dépend de `xethron/laravel-4-generators` (mort) et `doctrine/dbal ~2.4`. La structure suit PSR-0, sans `strict_types`, et l'API publique est `php artisan migrate:generate`.

### 1.2 Vision

Réécriture complète sous le nom `groupesti/laravel-migrations-generator`, namespace `GroupeSTI\MigrationsGenerator\`, ciblant **PHP 8.4+** et **Laravel 13+** uniquement. Architecture moderne basée sur des **drivers par moteur de BD** + **actions sur DTOs typés immuables**. La commande publique `php artisan migrate:generate` est conservée.

Cinq moteurs de bases de données sont supportés en première ligne :

- MySQL 8.0+
- MariaDB 10.6+
- PostgreSQL 14+
- SQLite 3.35+
- SQL Server 2019+

### 1.3 Hors scope (Phase 2 future)

- Procédures stockées
- Triggers
- Events MySQL / MariaDB
- Génération de seeders depuis les données existantes des tables

Ces points feront l'objet d'un brainstorming distinct lorsque v1.0 sera stable et que le besoin réel sera mesuré.

---

## 2. Architecture

### 2.1 Vue d'ensemble en couches

```
┌──────────────────────────────────────────────────────────────┐
│  Console Layer                                                │
│  GenerateCommand  →  parse flags  →  orchestre               │
└──────────┬────────────────────────────────────┬──────────────┘
           ▼                                    ▼
┌──────────────────────────┐     ┌────────────────────────────┐
│  Reader Pipeline         │     │  Writer Pipeline           │
│  (Actions)               │     │  (Actions)                 │
│                          │     │                            │
│  ReadDatabaseAction      │     │  RenderMigrationAction     │
│  (étapes internes :      │     │  (utilise les Renderers :  │
│   getTables → getColumns │     │   ColumnRenderer,          │
│   → getIndexes → getFKs  │     │   IndexRenderer,           │
│   → getViews)            │     │   ForeignKeyRenderer,      │
│                          │     │   ViewRenderer)            │
│                          │     │                            │
│                          │     │  WriteMigrationFileAction  │
└──────────┬───────────────┘     └────────────▲───────────────┘
           ▼                                  │
┌─────────────────────────────────────────────────────────────┐
│  Schema DTOs (final readonly)                               │
│  TableSchema · ColumnSchema · IndexSchema · ForeignKeySchema│
│  ViewSchema · DatabaseSchema (root)                         │
└──────────▲──────────────────────────────────────────────────┘
           │
┌──────────┴──────────────────────────────────────────────────┐
│  Schema Drivers (1 par moteur, interface commune)           │
│  SchemaDriver (interface)                                   │
│  ├─ MySqlDriver     (MySQL 8.0+)                            │
│  ├─ MariaDbDriver   (MariaDB 10.6+)                         │
│  ├─ PostgresDriver  (PostgreSQL 14+)                        │
│  ├─ SqliteDriver    (SQLite 3.35+)                          │
│  └─ SqlServerDriver (SQL Server 2019+)                      │
│                                                              │
│  Chaque driver utilise le Schema Builder Laravel natif      │
│  (Schema::getTables, getColumns, getIndexes, getForeignKeys)│
│  + requêtes SQL propres au moteur pour les détails.         │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Principes architecturaux

1. **Driver-isolated** — chaque moteur de BD est encapsulé dans une classe ; ajouter un nouveau moteur = 1 classe + sa suite de tests.
2. **DTO-centric** — toute donnée transite via DTOs `final readonly`. Les Actions ne se passent jamais de tableaux flous.
3. **Stateless Actions** — chaque Action est un service `final readonly` injectable. Pas d'état partagé. Testables unitairement avec mocks de `SchemaDriver`.
4. **Stub-driven rendering** — la génération de code PHP utilise des stubs (`{{tableName}}`, `{{columns}}`) publiables par le consommateur via `vendor:publish`.
5. **Strict typing partout** — `declare(strict_types=1);`, types explicites sur tous les paramètres et retours, PHPStan niveau 8.

### 2.3 Doctrine/DBAL

Abandonné totalement. Laravel 11+ expose `Schema::getTables()`, `getColumns()`, `getIndexes()`, `getForeignKeys()` nativement, ce qui couvre la majorité des besoins. Les détails spécifiques par moteur (jsonb PG, fulltext MySQL, partial indexes PG, etc.) sont obtenus par requêtes SQL natives dans le driver concerné.

---

## 3. Composants et structure des fichiers

### 3.1 Arborescence

```
groupesti/laravel-migrations-generator/
├── composer.json
├── phpunit.xml                          # config Pest
├── pint.json                            # Laravel Pint
├── phpstan.neon                         # PHPStan/Larastan niveau 8
├── .github/workflows/tests.yml          # CI matrice 5 moteurs
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
├── CODE_OF_CONDUCT.md
├── SECURITY.md
├── UPGRADING.md
├── LICENSE.md
├── stubs/                               # publishables via vendor:publish
│   ├── migration.create.stub
│   ├── migration.foreign-keys.stub
│   └── migration.view.stub
├── config/
│   └── migrations-generator.php
├── src/
│   ├── MigrationsGeneratorServiceProvider.php
│   ├── Console/
│   │   └── GenerateCommand.php          # signature : migrate:generate
│   ├── Contracts/
│   │   ├── SchemaDriver.php
│   │   ├── StubRenderer.php
│   │   └── MigrationWriter.php
│   ├── Drivers/
│   │   ├── AbstractSchemaDriver.php     # fonctions communes
│   │   ├── MySqlDriver.php
│   │   ├── MariaDbDriver.php
│   │   ├── PostgresDriver.php
│   │   ├── SqliteDriver.php
│   │   ├── SqlServerDriver.php
│   │   ├── SchemaDriverFactory.php
│   │   └── Resolvers/
│   │       ├── ColumnTypeResolver.php
│   │       └── DefaultValueResolver.php
│   ├── Schema/                          # DTOs immutables
│   │   ├── DatabaseSchema.php           # racine
│   │   ├── TableSchema.php
│   │   ├── ColumnSchema.php
│   │   ├── IndexSchema.php
│   │   ├── ForeignKeySchema.php
│   │   ├── ViewSchema.php
│   │   └── Enums/
│   │       ├── ColumnType.php           # VARCHAR, BIGINT, JSON, JSONB, ...
│   │       ├── IndexType.php            # PRIMARY, UNIQUE, INDEX, FULLTEXT, SPATIAL
│   │       └── OnAction.php             # CASCADE, RESTRICT, SET_NULL, NO_ACTION
│   ├── Actions/
│   │   ├── ReadDatabaseAction.php
│   │   ├── BuildMigrationPlanAction.php
│   │   ├── RenderMigrationAction.php
│   │   └── WriteMigrationFileAction.php
│   ├── Plan/
│   │   ├── MigrationPlan.php
│   │   └── MigrationFile.php
│   ├── Renderers/
│   │   ├── ColumnRenderer.php
│   │   ├── IndexRenderer.php
│   │   ├── ForeignKeyRenderer.php
│   │   └── ViewRenderer.php
│   ├── Stub/
│   │   └── DefaultStubRenderer.php
│   ├── Writers/
│   │   └── FilesystemMigrationWriter.php   # implémente MigrationWriter
│   ├── Support/
│   │   └── GenerateOptions.php          # DTO des options CLI parsées
│   └── Exceptions/
│       ├── MigrationsGeneratorException.php
│       ├── ConfigurationException.php
│       ├── UnsupportedDriverException.php
│       ├── InvalidConnectionException.php
│       ├── SchemaReadException.php
│       ├── TableNotFoundException.php
│       ├── DatabaseAccessDeniedException.php
│       ├── GenerationException.php
│       ├── UnsupportedColumnTypeException.php
│       ├── StubNotFoundException.php
│       ├── CircularForeignKeyException.php
│       ├── WriteException.php
│       ├── FileAlreadyExistsException.php
│       └── WriteFailedException.php
└── tests/
    ├── Pest.php
    ├── TestCase.php                     # extends Orchestra Testbench
    ├── Feature/
    │   ├── Cli/                         # E2E commande
    │   ├── Mysql/
    │   ├── MariaDb/
    │   ├── Postgres/
    │   ├── Sqlite/
    │   └── SqlServer/
    └── Unit/
        ├── Renderers/
        ├── Schema/
        ├── Actions/
        └── Drivers/
```

### 3.2 Responsabilités

| Composant | Responsabilité unique |
|---|---|
| `GenerateCommand` | Parser les flags, instancier le driver via `SchemaDriverFactory`, orchestrer Read → Plan → Render → Write, afficher la progression console |
| `SchemaDriver` (interface) | `getTables()`, `getColumns(table)`, `getIndexes(table)`, `getForeignKeys(table)`, `getViews()` |
| `MySqlDriver` etc. | Implémente `SchemaDriver` en lisant `INFORMATION_SCHEMA` / `pg_catalog` / `sqlite_master` / `sys.*` selon le moteur |
| `SchemaDriverFactory` | Sélectionne le driver à partir du nom de connexion Laravel (`config('database.connections.X.driver')`) |
| `ColumnTypeResolver` | Mappe `varchar(255)` → `string('x', 255)`, `bigint unsigned` → `unsignedBigInteger('x')`, `jsonb` → `jsonb('x')`, etc. |
| `DefaultValueResolver` | Décode `CURRENT_TIMESTAMP`, `nextval('seq')`, `NULL`, valeurs littérales en sortie `->default(...)` propre |
| `ReadDatabaseAction` | Pure orchestration : prend `SchemaDriver` + filtres (`tables` / `ignore`), retourne `DatabaseSchema` |
| `BuildMigrationPlanAction` | Décide combien de fichiers, dans quel ordre, applique `--squash` / `--no-foreign-keys`, retourne `MigrationPlan` |
| `RenderMigrationAction` | Pour chaque `MigrationFile`, applique le stub via `StubRenderer`, retourne le code PHP final |
| `WriteMigrationFileAction` | Écrit physiquement, gère collisions (`--force`), retourne la liste des paths écrits |
| `Renderers/*` | Conversion DTO → ligne(s) Blueprint (`$table->string('email')->unique();`) |

### 3.3 DTOs principaux (esquisses)

```php
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

> Note : `ColumnType` est un Enum PHP (cas résiduel autorisé : valeur purement technique, immuable, jamais admin). Pas un Reference Model — le projet est un package autonome, pas une application GSTI.

---

## 4. Flux de données de bout en bout

### 4.1 Pipeline complet

```
┌────────────────────────────────────────────────────────────────────┐
│ 1. CLI Entry                                                        │
│    php artisan migrate:generate users,posts --connection=pgsql      │
│    --with-views --squash                                            │
└──────────────────────────────┬─────────────────────────────────────┘
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│ 2. GenerateCommand::handle()                                       │
│    a. parse flags  →  GenerateOptions DTO (final readonly)         │
│    b. resolve driver via SchemaDriverFactory                       │
│       'mysql'→MySqlDriver, 'pgsql'→PostgresDriver, etc.            │
│       (driver inconnu → UnsupportedDriverException)                 │
└──────────────────────────────┬─────────────────────────────────────┘
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│ 3. ReadDatabaseAction::execute(driver, options)                    │
│    Pour chaque table sélectionnée (filter --tables / --ignore) :    │
│      - driver->getColumns(table)    →  ColumnSchema[]              │
│      - driver->getIndexes(table)    →  IndexSchema[]               │
│      - driver->getForeignKeys(t)    →  ForeignKeySchema[]          │
│    Si --with-views :                                                │
│      - driver->getViews()           →  ViewSchema[]                │
│    Construit DatabaseSchema (root DTO)                              │
└──────────────────────────────┬─────────────────────────────────────┘
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│ 4. BuildMigrationPlanAction::execute(schema, options)              │
│    a. Tri topologique des tables par FKs (algorithme de Kahn)      │
│       → tables sans FKs en premier, dépendantes après               │
│       → cycle détecté + flag absent → CircularForeignKeyException   │
│                                                                     │
│    b. Stratégie selon flags :                                       │
│       Default     : 2 fichiers (create_*_tables, foreign_keys)     │
│       --squash    : 1 fichier unique avec tout                     │
│       --no-fks    : 1 fichier sans bloc FK                         │
│       +views      : +1 fichier create_*_views                      │
│                                                                     │
│    c. Génère noms de fichiers : {date}_{order}_{slug}.php          │
│       (date = options.date ?? now())                                │
│                                                                     │
│    Retourne MigrationPlan (1+ MigrationFile DTOs)                   │
└──────────────────────────────┬─────────────────────────────────────┘
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│ 5. RenderMigrationAction::execute(plan)                            │
│    Pour chaque MigrationFile :                                      │
│      - charge le stub (--stub-path > publié > vendor)               │
│      - assemble columns / indexes / FKs via Renderers               │
│      - remplit placeholders {{tables}}, {{className}}, {{up}},     │
│        {{down}}                                                     │
│      - retourne le PHP final (string)                               │
└──────────────────────────────┬─────────────────────────────────────┘
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│ 6. WriteMigrationFileAction::execute(rendered, options)            │
│    Pour chaque fichier :                                            │
│      - vérifie collision (file_exists)                              │
│        - sans --force → FileAlreadyExistsException                  │
│        - avec --force → écrase                                      │
│      - écrit dans options.path (ou database/migrations/ default)    │
│    Retourne array de paths écrits.                                  │
└──────────────────────────────┬─────────────────────────────────────┘
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│ 7. Output console (recap)                                           │
│    ✓ Generated 3 migration files in database/migrations/           │
│      - 2026_05_07_120000_create_users_table.php                     │
│      - 2026_05_07_120001_create_posts_table.php                     │
│      - 2026_05_07_120002_add_foreign_keys.php                       │
└────────────────────────────────────────────────────────────────────┘
```

### 4.2 Contrat CLI consolidé

```bash
php artisan migrate:generate [tables?] [options]
```

| Argument / Option | Type | Défaut | Description |
|---|---|---|---|
| `tables` (positionnel) | csv string | `null` (= toutes) | Tables à générer |
| `--connection=` | string | connexion par défaut | Connexion DB Laravel |
| `--ignore=` | csv string | — | Tables à exclure |
| `--tables=` | csv string | — | Alias du positionnel |
| `--path=` | string | `database/migrations` | Dossier de sortie |
| `--stub-path=` | string | stubs publiés ou vendor | Dossier des stubs |
| `--squash` | bool | false | Un seul fichier de migration |
| `--with-views` | bool | false | Inclure les vues |
| `--no-foreign-keys` | bool | false | N'écrit pas le fichier FK |
| `--no-anonymous` | bool | false | Classes nommées (legacy) |
| `--default-index-names` | bool | false | Forcer noms d'index Laravel par défaut |
| `--default-fk-names` | bool | false | Forcer noms de FK Laravel par défaut |
| `--prefix=` | string | depuis `config('database.connections.{conn}.prefix')` | Préfixe de table custom |
| `--date=YYYY-MM-DD` | string | now() | Date du préfixe de fichier |
| `--force` | bool | false | Écrase les fichiers existants |

### 4.3 Codes de sortie

| Code | Signification |
|---|---|
| `0` | Succès — fichiers générés |
| `1` | Erreur de configuration (connexion, driver inconnu) |
| `2` | Erreur de lecture BD (table introuvable, perm. refusée) |
| `3` | Erreur de génération (stub introuvable, type non supporté) |
| `4` | Erreur d'écriture (fichier existant sans `--force`, IO) |

---

## 5. Gestion d'erreurs

### 5.1 Hiérarchie d'exceptions

```
\RuntimeException
└── MigrationsGeneratorException                  (parent du package)
    ├── ConfigurationException                    (exit 1)
    │   ├── UnsupportedDriverException
    │   └── InvalidConnectionException
    ├── SchemaReadException                       (exit 2)
    │   ├── TableNotFoundException
    │   └── DatabaseAccessDeniedException
    ├── GenerationException                       (exit 3)
    │   ├── UnsupportedColumnTypeException
    │   ├── StubNotFoundException
    │   └── CircularForeignKeyException
    └── WriteException                            (exit 4)
        ├── FileAlreadyExistsException
        └── WriteFailedException
```

**Règles :**

- Toutes les exceptions du package héritent de `MigrationsGeneratorException` → un consommateur peut catcher uniquement notre namespace.
- Chaque exception transporte un **contexte** structuré (`->context(): array`) : table, driver, type SQL non supporté, etc.
- Le `GenerateCommand` les attrape, mappe vers le bon exit code, et imprime un message localisé.

### 5.2 Stratégie de fallback

| Cas | Comportement |
|---|---|
| Type de colonne **inconnu mais reproductible** (le moteur cible accepte la définition SQL brute, ex: `JSONB` PG sur driver PG via `->raw('jsonb')`) | Warning console, génère commentaire `// unmapped`, utilise `->raw($sql)` qui reste exécutable par `migrate` |
| Type de colonne **inconnu et non reproductible** (le SQL brut référence une extension absente, ex: PostGIS `geometry` sur driver MySQL ; ou type composite PG sans équivalent Schema Builder) | Lève `UnsupportedColumnTypeException` avec contexte `{table, column, type, driver}` |
| FK référence une table **non incluse** dans `--tables` | Warning console, FK omise du fichier, table maintenue |
| FK **cyclique** entre 2+ tables | Lève `CircularForeignKeyException`, suggère `--squash` ou `--no-foreign-keys` |
| Stub **introuvable** | `StubNotFoundException` immédiate |
| Fichier **existant** sans `--force` | `FileAlreadyExistsException` ; suggère `--force` ou `--path=` |
| Connexion **morte** mid-run | `SchemaReadException` qui wrap la `PDOException` originale |
| Vue avec **dépendance non résolue** (autre vue manquante) | Warning, vue mise en queue, retry après autres ; si toujours échec → omise + warning final |

### 5.3 Logging et observabilité

- Pas de logger Laravel (`Log::`). Tout passe par l'`OutputInterface` Symfony Console (`$this->info`, `$this->warn`, `$this->error`).
- Mode `-v / -vv / -vvv` :
  - `-v` : détaille chaque table lue
  - `-vv` : + détaille colonnes/indexes/FKs lues
  - `-vvv` : + dump le SQL des requêtes vers `INFORMATION_SCHEMA` / `pg_catalog` / `sqlite_master` / `sys.*`

---

## 6. Stratégie de tests

### 6.1 Pyramide

```
       ┌──────────────────────┐
       │  E2E (CLI réelle)    │  ~10 tests
       │  ./vendor/bin/pest   │
       └──────────────────────┘
      ┌────────────────────────┐
      │  Feature par moteur    │  ~30/moteur × 5 = ~150 tests
      └────────────────────────┘
    ┌──────────────────────────┐
    │  Unit (isolés, mocks)    │  ~150+ tests
    └──────────────────────────┘
```

### 6.2 Tests Unit (sans BD)

| Cible | Cas couverts |
|---|---|
| `ColumnRenderer` | Tous types : string, char, text, int variants, decimal, json, jsonb, uuid, enum, geometry, blob, timestamps, generated cols, virtual, default, nullable, unsigned, autoincrement |
| `IndexRenderer` | Primary, unique, simple, composite, fulltext, spatial, partial (PG), descending |
| `ForeignKeyRenderer` | onDelete cascade/restrict/set null/no action, onUpdate idem, FK composite |
| `ViewRenderer` | Vues simples, materialized (PG), with check option |
| `DefaultValueResolver` | `CURRENT_TIMESTAMP`, `nextval`, littéraux string/int/bool/null, expressions |
| `ColumnTypeResolver` | Mapping exhaustif par moteur via dataset Pest |
| DTOs | Construction valide, immuabilité, sérialisation pour debug |
| Actions | Avec `SchemaDriver` mocké via Mockery, vérifie orchestration |
| `BuildMigrationPlanAction` | Tri topologique : datasets avec/sans cycles, --squash, --no-fks |

### 6.3 Tests Feature par moteur (avec BD réelle)

Pour chaque moteur dans la matrice CI :

```php
beforeEach(function () {
    DB::unprepared(file_get_contents(__DIR__.'/Fixtures/sample.sql'));
});

it('reads tables from MySQL and produces deterministic migration files', function () {
    $files = artisan('migrate:generate', [
        '--connection' => 'mysql',
        '--path' => storage_path('test-migrations'),
    ])->run();

    expect($files)->toHaveCount(2)
        ->and($files[0])->toMatchSnapshot('mysql/create_tables.php')
        ->and($files[1])->toMatchSnapshot('mysql/foreign_keys.php');
});
```

**Fixtures SQL** : un schéma de référence par moteur, qui couvre :

- 6+ tables avec relations 1:N, N:N, self-FK
- Tous les types principaux (string, int, decimal, json/jsonb, datetime, blob, geometry où dispo)
- Index simples, composites, unique, fulltext, spatial
- FKs avec actions diverses
- 1 vue
- Tables avec/sans timestamps, soft deletes

**Snapshots** : les fichiers générés sont vérifiés via `pest-plugin-snapshots`. Modification volontaire = régénération du snapshot dans le commit.

### 6.4 Tests E2E

`tests/Feature/Cli/E2eTest.php` lance la commande complète via `Artisan::call()`, vérifie :

- Le retour console
- Les fichiers physiquement écrits
- Que `php artisan migrate` sur ces fichiers reconstruit la BD à l'identique
- Round-trip : dump initial → migrate:generate → fresh migrate → dump final → diff = vide

### 6.5 Matrice CI GitHub Actions

```yaml
strategy:
  matrix:
    php: ['8.4']
    laravel: ['13.*']
    db:
      - mysql:8.0
      - mysql:8.4
      - mariadb:11
      - postgres:16
      - sqlite (file)
      - sqlserver:2022
```

Chaque cellule lance la suite Feature de son driver + l'intégralité des Unit. Coverage agrégée à la fin, gate ≥ 80 %.

### 6.6 Hors scope tests v1.0

- Tests de performance (lecture d'une BD à 500+ tables)
- Tests de fuzzing
- Tests de mutation (Infection)

Ces points sont candidats pour v2.0 si pertinents.

---

## 7. Distribution

### 7.1 Packagist

- **Nom** : `groupesti/laravel-migrations-generator`
- **Type** : `library`
- **Visibilité** : publique
- **Tags** : `laravel`, `migration`, `generator`, `database`, `schema`, `reverse-engineering`, `mysql`, `postgresql`, `mariadb`, `sqlite`, `sqlserver`

### 7.2 `composer.json` cible

```json
{
    "name": "groupesti/laravel-migrations-generator",
    "description": "Generate Laravel 13+ migrations from an existing database (MySQL, MariaDB, PostgreSQL, SQLite, SQL Server).",
    "keywords": ["laravel", "migration", "generator", "database", "schema"],
    "license": "MIT",
    "authors": [
        { "name": "Groupe STI", "homepage": "https://groupesti.com" },
        { "name": "Bernhard Breytenbach (original)", "email": "bernhard@coffeecode.co.za" }
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
        "psr-4": { "GroupeSTI\\MigrationsGenerator\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "GroupeSTI\\MigrationsGenerator\\Tests\\": "tests/" }
    },
    "extra": {
        "laravel": {
            "providers": ["GroupeSTI\\MigrationsGenerator\\MigrationsGeneratorServiceProvider"]
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### 7.3 Versioning et workflow

- **SemVer strict**
- v1.0.0 = première release publique stable (Phase 1)
- v1.x.y = bug fixes / nouveaux moteurs / nouveaux types de colonnes (rétrocompat)
- v2.0.0 = Phase 2 (procédures, triggers, events, seeders) si jugée pertinente
- Branche `main` = production, branche `develop` = intégration
- Tag git `v1.0.0` → release GitHub → publication automatique Packagist (webhook)

### 7.4 ServiceProvider

```php
final class MigrationsGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/migrations-generator.php', 'migrations-generator');

        $this->app->bind(StubRenderer::class, DefaultStubRenderer::class);
        $this->app->bind(MigrationWriter::class, FilesystemMigrationWriter::class);
        $this->app->singleton(SchemaDriverFactory::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([GenerateCommand::class]);

            $this->publishes([
                __DIR__.'/../stubs' => $this->app->basePath('stubs/migrations-generator'),
            ], 'migrations-generator-stubs');

            $this->publishes([
                __DIR__.'/../config/migrations-generator.php' => config_path('migrations-generator.php'),
            ], 'migrations-generator-config');
        }
    }
}
```

---

## 8. Documentation — pack `.md` complet

| Fichier | Contenu |
|---|---|
| `README.md` | Badges (build, downloads, license, Packagist), description, prérequis (PHP 8.4+, Laravel 13+), installation (`composer require --dev`), usage de base + tous les flags, sections par moteur, exemples de stubs custom, troubleshooting, lien CHANGELOG, lien CONTRIBUTING, crédits |
| `CHANGELOG.md` | Format **Keep a Changelog** + SemVer, sections `Unreleased / Added / Changed / Deprecated / Removed / Fixed / Security` |
| `CONTRIBUTING.md` | Setup local (clone, composer install, lancer Pest sur un moteur unique), conventions de code (PSR-12 + Pint), comment ajouter un nouveau moteur (template de driver + tests), Conventional Commits, processus de PR, checklist avant merge |
| `CODE_OF_CONDUCT.md` | Contributor Covenant 2.1 standard |
| `SECURITY.md` | Politique de divulgation responsable, contact email, versions supportées |
| `UPGRADING.md` | Guide de migration depuis `xethron/migrations-generator` (changement de namespace, flags renommés, dépendances supprimées) |
| `LICENSE.md` | MIT — préserve le copyright original Bernhard Breytenbach + ajoute Groupe STI |

### 8.1 Structure du README

```markdown
# Laravel Migrations Generator

[![Tests](badge)] [![Packagist](badge)] [![License MIT](badge)] [![PHP 8.4+](badge)]

> Generate Laravel 13+ migrations from an existing database.
> Supports MySQL, MariaDB, PostgreSQL, SQLite, SQL Server.

## Requirements
- PHP 8.4+
- Laravel 13+
- One of: MySQL 8.0+, MariaDB 10.6+, PostgreSQL 14+, SQLite 3.35+, SQL Server 2019+

## Installation
## Quick start
## Command reference
## Per-engine notes
## Custom stubs
## Programmatic API
## Troubleshooting
## Contributing
## Credits & history
## License
```

---

## 9. Roadmap v2.0 (hors scope v1.0)

| Feature | Effort estimé | Priorité |
|---|---|---|
| Procédures stockées (MySQL/MariaDB/PG/MSSQL) | Élevé | Moyen |
| Triggers (4 moteurs) | Élevé | Moyen |
| Events (MySQL/MariaDB only) | Faible | Faible |
| Seeders depuis données (chunked, FK-aware) | Très élevé | Faible |
| Generated columns avancées | Moyen | Moyen |
| Vues matérialisées PG | Faible | Moyen |
| Driver CockroachDB | Moyen | Bas |
| Web UI (plugin Filament) | Élevé | Bas |

Ces features feront l'objet d'un brainstorming dédié quand v1.0 sera stable.

---

## 10. Critères de succès v1.0

- [ ] Suite Pest passe à 100 % sur les 5 moteurs en CI
- [ ] Coverage ≥ 80 %
- [ ] PHPStan niveau 8 = 0 erreur
- [ ] Pint = 0 warning
- [ ] Round-trip (dump → generate → migrate → dump = identique) validé sur fixtures
- [ ] Pack `.md` documentaire complet et à jour
- [ ] `UPGRADING.md` testé manuellement par migration d'un projet réel
- [ ] Première release v1.0.0 sur Packagist
- [ ] Au moins 1 projet GSTI en interne consomme le package en remplacement de l'ancien

---

## 11. Décisions clés enregistrées (ADR-style)

| # | Décision | Motif |
|---|---|---|
| 1 | Cible Laravel 13+ exclusivement | Schema Builder natif disponible, pas besoin de DBAL |
| 2 | Abandon `doctrine/dbal` | Laravel 11+ a une introspection native suffisante |
| 3 | Abandon `xethron/laravel-4-generators` | Dépendance morte, on internalise le rendu de stubs |
| 4 | 5 moteurs en v1 (MySQL, MariaDB, PG, SQLite, SQL Server) | Le maximum réaliste avec une matrice CI raisonnable |
| 5 | Driver-based + Actions/DTOs | Isolation par moteur, testabilité, ajout d'un nouveau moteur peu coûteux |
| 6 | Classes anonymes par défaut | Standard Laravel 8+, plus aucun nom à inventer |
| 7 | Renommer `--templatePath` en `--stub-path` (breaking) | Cohérence avec le vocabulaire Laravel actuel |
| 8 | Phase 2 hors scope v1.0 | Rate-limit la complexité initiale, livre une v1 utilisable rapidement |
| 9 | Pas de `Log::`, tout via `OutputInterface` | C'est une commande artisan synchrone, pas une queue |
| 10 | Snapshots pour les tests Feature | Détection automatique des régressions de format |
