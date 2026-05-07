# Module Migration GSTI — Design v1.0

| Champ | Valeur |
|---|---|
| **Date** | 2026-05-07 |
| **Statut** | Validé (brainstorming) |
| **Auteur** | Richard Simard (Groupe STI) |
| **Distribution** | Hybride : package Composer + module GSTI copyable |
| **Repo** | `/var/www/packages/laravel-migrations-generator` |
| **Cible release** | v1.0.0 (Generator 5 moteurs + skeleton Extract/Import) |
| **Supersedes** | [`2026-05-07-laravel-13-modernization-design.md`](./2026-05-07-laravel-13-modernization-design.md) |

---

## 1. Contexte et vision

### 1.1 Pivot architectural

Le projet, initialement designé comme un package Composer standalone (`groupesti/laravel-migrations-generator`, namespace `GroupeSTI\MigrationsGenerator`), pivote vers une architecture de **module GSTI hybride** :

- Le repo `/var/www/packages/laravel-migrations-generator/` héberge le code sous la forme d'un **module GSTI** complet, suivant le template `/var/www/packages/Module/`.
- Le module porte le namespace `App\Modules\Migration\` et peut être :
  - **consommé via Composer** (path repository ou Packagist) — le code s'installe alors directement dans `app/Modules/Migration/` de l'app consommatrice ;
  - **copié/symlinké** dans `app/Modules/Migration/` d'une app GSTI sans passer par Composer.

### 1.2 Vision finale

Un module **`Migration`** GSTI qui regroupe trois capacités complémentaires de manipulation de schéma et de données :

| Sous-feature | Rôle |
|---|---|
| **Generator** | Génère des fichiers de migration Laravel 13+ depuis une BD existante (5 moteurs : MySQL, MariaDB, PostgreSQL, SQLite, SQL Server) |
| **Extract** | Extrait les données d'une ou plusieurs tables vers un fichier (CSV, JSON, Excel — extensible) |
| **Import** | Importe des données depuis un fichier (CSV, JSON, Excel — extensible) vers une BD |

### 1.3 Phasage

| Phase | Livrable | Statut |
|---|---|---|
| **0** | Squelette modulaire complet vide (4 niveaux + ServiceProviders + outillage) | À faire |
| **1** | Generator SQLite (v0.1.0-alpha) | À faire |
| **2** | Generator MySQL (v0.2.0-alpha) | Plan distinct |
| **3** | Generator MariaDB (v0.3.0-alpha) | Plan distinct |
| **4** | Generator PostgreSQL (v0.4.0-beta) | Plan distinct |
| **5** | Generator SQL Server (v0.9.0-rc) | Plan distinct |
| **6** | E2E + CI matrix complète + docs + release v1.0.0 | Plan distinct |
| **7+** | Extract — CSV puis JSON puis Excel (3 plans incrémentaux) | Brainstorming futur |
| **10+** | Import — CSV puis JSON puis Excel (3 plans incrémentaux) | Brainstorming futur |

---

## 2. Architecture

### 2.1 Hiérarchie des modules (4 niveaux)

```
App\Modules\Migration\                            ← Niveau 1 : module wrapper
└── Modules\
    └── Migration\                                ← Niveau 2 : sous-module core
        └── Modules\
            ├── Generator\                        ← Niveau 3 : sous-sous-module
            ├── Extract\                          ← Niveau 3
            └── Import\                           ← Niveau 3
```

**Rôle de chaque niveau :**

| Niveau | Nom | Rôle |
|---|---|---|
| 1 | `Migration` (wrapper) | Point d'entrée du module GSTI. Enregistre le sous-module core via son ServiceProvider. Tient les concerns transversaux (config racine, alias, façades publiques). |
| 2 | `Migration` (core) | Cœur fonctionnel partagé : DTOs communs (`TableSchema`, `ColumnSchema`, etc.), exceptions de base (`MigrationException`), interfaces (`SchemaDriver`, `Reader`, `Writer`), Resolvers transversaux. |
| 3 | `Generator` / `Extract` / `Import` | Implémentations spécifiques. Chacun a son CLI command, ses Actions, ses drivers/formats, et son ServiceProvider. |

### 2.2 Pourquoi 4 niveaux ?

- **Niveau 1 (wrapper)** : isole le module du reste de l'app via un seul ServiceProvider à enregistrer dans `bootstrap/providers.php`. Simplifie l'installation côté consommateur.
- **Niveau 2 (core)** : factorise les abstractions (DTOs, interfaces, resolvers) que **les trois sous-features partagent**. Évite la duplication de code (ex: `TableSchema` est utile pour Generator pour décrire ce qu'on lit, pour Extract pour décrire ce qu'on dump, et pour Import pour valider la cible).
- **Niveau 3 (features)** : isolation maximale. Chaque feature peut être désactivée individuellement, testée séparément, livrée incrémentalement.

### 2.3 ServiceProviders

```
Module                                            ServiceProvider                                   Rôle
─────────────────────────────────────────────────────────────────────────────────────────────────────────────
App\Modules\Migration                             MigrationServiceProvider (level 1)                Enregistre core; expose la config publique; publie les ressources globales
App\Modules\Migration\Modules\Migration           MigrationServiceProvider (level 2)                Enregistre les bindings core (interfaces); enregistre G/E/I providers
App\Modules\Migration\Modules\Migration\Modules\Generator   GeneratorServiceProvider                Enregistre la commande migrate:generate, les drivers DB
App\Modules\Migration\Modules\Migration\Modules\Extract     ExtractServiceProvider                  Enregistre la commande migrate:extract, les writers de format
App\Modules\Migration\Modules\Migration\Modules\Import      ImportServiceProvider                   Enregistre la commande migrate:import, les readers de format
```

**Convention de nommage** : malgré le namespace identique (`MigrationServiceProvider` à 2 niveaux différents), PHP les distingue via le namespace complet. Pour la lisibilité, on utilise des **alias dans `bootstrap/providers.php`** :

```php
return [
    \App\Modules\Migration\Providers\MigrationServiceProvider::class,
    // Le provider de niveau 1 enregistre transitivement ceux des niveaux 2 et 3.
];
```

### 2.4 Distribution hybride (Composer ET copyable)

Le repo a un `composer.json` avec PSR-4 mappé à la racine :

```json
{
    "name": "groupesti/migration-module",
    "autoload": {
        "psr-4": {
            "App\\Modules\\Migration\\": ""
        }
    },
    "extra": {
        "laravel": {
            "providers": ["App\\Modules\\Migration\\Providers\\MigrationServiceProvider"]
        }
    }
}
```

> Note : le mapping `"App\\Modules\\Migration\\": ""` signifie que **la racine du repo** EST l'arbre du module. Aucun préfixe `src/` — on suit le template Module GSTI à la lettre.

> ⚠️ **Condition d'installation mode 1** : l'app consommatrice ne doit **pas** avoir un module local `app/Modules/Migration/` qui rentrerait en conflit de namespace avec le package. Si c'est le cas, basculer en mode 2 (copy/symlink) ou renommer le module local.

Deux modes d'installation :

#### Mode 1 — Via Composer (path repository)

Dans le `composer.json` de l'app consommatrice :

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

Composer crée un symlink dans `vendor/groupesti/migration-module/` qui pointe vers le repo. L'autoload PSR-4 fait que les classes `App\Modules\Migration\…` sont automatiquement disponibles dans l'app **sans** rien copier dans `app/Modules/`.

**Inconvénient** : le code physique vit dans `vendor/` plutôt que dans `app/Modules/`, ce qui peut surprendre les conventions GSTI. Les migrations, traductions, vues sont quand même chargées par les ServiceProviders.

#### Mode 2 — Copy/symlink dans `app/Modules/`

Pour les apps GSTI strictement conformes au pattern :

```bash
# Symlink (dev local, miroir en temps réel)
ln -s /var/www/packages/laravel-migrations-generator app/Modules/Migration

# OU copie (déploiement immutable, version-locked)
cp -r /var/www/packages/laravel-migrations-generator app/Modules/Migration
rm -rf app/Modules/Migration/.git
```

Dans ce cas, l'app consommatrice n'a **rien à modifier** dans son `composer.json` : son autoload `App\Modules\` couvre déjà l'arbre.

#### Choix par app consommatrice

Documenté dans `Docs/INSTALLATION.md`. Les deux modes coexistent : un même repo de module, deux modes de consommation selon les préférences de l'équipe consommatrice.

### 2.5 Principes architecturaux

1. **Strict typing** — `declare(strict_types=1);` partout, types explicites, PHPStan niveau 8.
2. **DTOs `final readonly`** — toute donnée structurée transite via DTO immuable.
3. **Actions `final readonly`** — chaque opération est une Action stateless avec une seule méthode publique `execute()`.
4. **Interfaces dans `Interfaces/`** — `SchemaDriver`, `Reader`, `Writer`, `StubRenderer`, `MigrationWriter`. Implémentations dans le sous-module concerné.
5. **Pas de logique métier dans les Models** — Eloquent uniquement pour la persistance (relations, scopes, casts).
6. **Result pattern pour erreurs métier prévisibles** — `App\Support\ValueObjects\Result` (réutilise la classe GSTI standard si présente dans l'app consommatrice ; package un fallback minimal sinon dans `Migration\Traits\` ou `Migration\Support\`).
7. **Exceptions typées pour erreurs exceptionnelles** — hiérarchie sous `MigrationDomainException`.
8. **Cache 1h sur lectures de Reference Models et Module Configs** — cohérent avec les règles GSTI.

---

## 3. Structure complète des fichiers

### 3.1 Vue d'ensemble (4 niveaux + template Module à chaque niveau pertinent)

```
laravel-migrations-generator/                         ← repo root = App\Modules\Migration\
│
├── composer.json                                     # PSR-4 mappe / vers App\Modules\Migration\
├── README.md                                         # vue d'ensemble Migration + 3 sous-features
├── CHANGELOG.md
├── LICENSE.md
├── CONTRIBUTING.md
├── CODE_OF_CONDUCT.md
├── SECURITY.md
├── UPGRADING.md
├── pint.json
├── phpstan.neon
├── phpunit.xml
├── .gitignore
├── .github/workflows/tests.yml
│
├── Actions/                                          ← Niveau 1 : actions transversales (vide initialement)
├── Config/
│   └── migration.php                                  # config racine (fallbacks, paths)
├── Console/
│   └── Commands/                                      # commandes transversales (vide)
├── Database/
│   ├── Migrations/                                    # vide (pas de tables propres pour le wrapper)
│   └── Seeders/
├── Docs/
│   ├── INSTALLATION.md                                # 2 modes : composer vs copy
│   ├── ARCHITECTURE.md                                # diagramme + niveaux + ServiceProviders
│   └── ROADMAP.md
├── DTOs/                                              # vide
├── Enums/                                             # vide
├── Events/                                            # vide
├── Exceptions/
│   └── MigrationDomainException.php                   # parent de toutes les exceptions du module
├── Http/
│   ├── Controllers/                                   # vide
│   ├── Requests/                                      # vide
│   └── Resources/                                     # vide
├── Interfaces/                                        # vide à ce niveau
├── Jobs/                                              # vide
├── lang/
│   ├── en/migration.php
│   ├── fr/migration.php
│   └── es/migration.php
├── Livewire/                                          # vide
├── Models/                                            # vide (le wrapper n'a pas de Model propre)
├── Policies/                                          # vide
├── Providers/
│   └── MigrationServiceProvider.php                   # niveau 1 — enregistre niveau 2 + concerns transversaux
├── resources/
│   ├── css/  js/  scss/  views/
├── routes/                                            # vide pour API/web (CLI uniquement)
├── Rules/                                             # vide
├── Services/                                          # vide
├── Traits/                                            # vide
│
└── Modules/                                          ← début du Niveau 2
    └── Migration/                                    ← App\Modules\Migration\Modules\Migration\
        │
        ├── Actions/                                  # Actions partagées (ex: ResolveColumnTypeAction)
        ├── Config/
        │   └── migration-core.php
        ├── Console/Commands/                         # commandes transversales du core (ex: migrate:status)
        ├── Database/{Migrations,Seeders}/
        ├── Docs/
        ├── DTOs/                                     ← DTOs partagés Generator/Extract/Import
        │   ├── DatabaseSchema.php
        │   ├── TableSchema.php
        │   ├── ColumnSchema.php
        │   ├── IndexSchema.php
        │   ├── ForeignKeySchema.php
        │   └── ViewSchema.php
        ├── Enums/                                    ← Enums techniques partagés
        │   ├── ColumnType.php
        │   ├── IndexType.php
        │   └── OnAction.php
        ├── Events/                                   # vide
        ├── Exceptions/
        │   ├── MigrationCoreException.php             # parent au sein du core
        │   ├── ConfigurationException.php
        │   ├── UnsupportedDriverException.php
        │   ├── InvalidConnectionException.php
        │   ├── SchemaReadException.php
        │   ├── TableNotFoundException.php
        │   ├── DatabaseAccessDeniedException.php
        │   ├── GenerationException.php
        │   ├── UnsupportedColumnTypeException.php
        │   ├── StubNotFoundException.php
        │   ├── CircularForeignKeyException.php
        │   ├── WriteException.php
        │   ├── FileAlreadyExistsException.php
        │   └── WriteFailedException.php
        ├── Http/{Controllers,Requests,Resources}/    # vide
        ├── Interfaces/
        │   ├── SchemaDriver.php
        │   ├── StubRenderer.php
        │   ├── MigrationWriter.php
        │   ├── Reader.php                             # pour Import (CSV/JSON/Excel)
        │   └── Writer.php                             # pour Extract
        ├── Jobs/                                     # vide
        ├── lang/{en,fr,es}/messages.php
        ├── Models/                                   # vide
        ├── Policies/                                 # vide
        ├── Providers/
        │   └── MigrationServiceProvider.php           # niveau 2 — enregistre G/E/I providers
        ├── resources/{css,js,scss,views}/            # vide
        ├── routes/                                   # vide
        ├── Rules/                                    # vide
        ├── Services/
        │   └── ColumnTypeResolver.php                 # mapping types DB partagé
        ├── Traits/
        │
        └── Modules/                                  ← début du Niveau 3
            │
            ├── Generator/                            ← App\Modules\Migration\Modules\Migration\Modules\Generator\
            │   ├── Actions/
            │   │   ├── ReadDatabaseAction.php
            │   │   ├── BuildMigrationPlanAction.php
            │   │   ├── RenderMigrationAction.php
            │   │   └── WriteMigrationFileAction.php
            │   ├── Config/generator.php
            │   ├── Console/Commands/
            │   │   └── GenerateCommand.php             # signature : migrate:generate
            │   ├── Database/{Migrations,Seeders}/    # vide
            │   ├── Docs/
            │   ├── DTOs/
            │   │   ├── GenerateOptions.php
            │   │   ├── MigrationPlan.php
            │   │   └── MigrationFile.php
            │   ├── Drivers/
            │   │   ├── AbstractSchemaDriver.php
            │   │   ├── MySqlDriver.php                # plan 2
            │   │   ├── MariaDbDriver.php              # plan 3
            │   │   ├── PostgresDriver.php             # plan 4
            │   │   ├── SqliteDriver.php               # plan 1
            │   │   ├── SqlServerDriver.php            # plan 5
            │   │   ├── SchemaDriverFactory.php
            │   │   └── Resolvers/
            │   │       └── DefaultValueResolver.php
            │   ├── Enums/                            # vide (utilise les Enums partagés de niveau 2)
            │   ├── Events/                           # vide
            │   ├── Exceptions/                       # vide (utilise hiérarchie de niveau 2)
            │   ├── Http/{Controllers,Requests,Resources}/  # vide
            │   ├── Interfaces/                        # vide (utilise interfaces de niveau 2)
            │   ├── Jobs/                              # vide
            │   ├── lang/{en,fr,es}/generator.php
            │   ├── Livewire/                          # vide
            │   ├── Models/                            # vide
            │   ├── Policies/                          # vide
            │   ├── Providers/
            │   │   └── GeneratorServiceProvider.php
            │   ├── Renderers/
            │   │   ├── ColumnRenderer.php
            │   │   ├── IndexRenderer.php
            │   │   ├── ForeignKeyRenderer.php
            │   │   └── ViewRenderer.php               # plan 6
            │   ├── resources/views/                   # vide
            │   ├── routes/                            # vide
            │   ├── Rules/                             # vide
            │   ├── Services/                          # vide
            │   ├── Stub/
            │   │   └── DefaultStubRenderer.php
            │   ├── stubs/
            │   │   ├── migration.create.stub
            │   │   ├── migration.foreign-keys.stub
            │   │   └── migration.view.stub            # plan 6
            │   ├── Traits/
            │   └── Writers/
            │       └── FilesystemMigrationWriter.php
            │
            ├── Extract/                              ← App\Modules\Migration\Modules\Migration\Modules\Extract\
            │   ├── Actions/                          # squelette vide (phases ultérieures)
            │   ├── Config/extract.php
            │   ├── Console/Commands/
            │   │   └── ExtractCommand.php             # signature : migrate:extract (stub)
            │   ├── Database/{Migrations,Seeders}/
            │   ├── Docs/
            │   ├── DTOs/                              # vide
            │   ├── Enums/                             # vide
            │   ├── Events/                            # vide
            │   ├── Exceptions/                        # vide
            │   ├── Formats/                           # implémentations concrètes (vide)
            │   │   ├── CsvWriter.php                  # phase ultérieure
            │   │   ├── JsonWriter.php                 # phase ultérieure
            │   │   └── ExcelWriter.php                # phase ultérieure
            │   ├── Http/{Controllers,Requests,Resources}/
            │   ├── Interfaces/                        # vide
            │   ├── Jobs/                              # vide
            │   ├── lang/{en,fr,es}/extract.php
            │   ├── Livewire/  Models/  Policies/      # vide
            │   ├── Providers/
            │   │   └── ExtractServiceProvider.php
            │   ├── resources/views/  routes/  Rules/  # vide
            │   ├── Services/                          # vide
            │   └── Traits/                            # vide
            │
            └── Import/                               ← App\Modules\Migration\Modules\Migration\Modules\Import\
                ├── Actions/                          # squelette vide
                ├── Config/import.php
                ├── Console/Commands/
                │   └── ImportCommand.php              # signature : migrate:import (stub)
                ├── Database/{Migrations,Seeders}/
                ├── Docs/
                ├── DTOs/  Enums/  Events/  Exceptions/  # vide
                ├── Formats/                           # vide
                │   ├── CsvReader.php                  # phase ultérieure
                │   ├── JsonReader.php                 # phase ultérieure
                │   └── ExcelReader.php                # phase ultérieure
                ├── Http/{Controllers,Requests,Resources}/
                ├── Interfaces/  Jobs/                 # vide
                ├── lang/{en,fr,es}/import.php
                ├── Livewire/  Models/  Policies/      # vide
                ├── Providers/
                │   └── ImportServiceProvider.php
                ├── resources/views/  routes/  Rules/  # vide
                ├── Services/  Traits/                 # vide
```

### 3.2 Tests — racine `tests/`

```
tests/
├── Pest.php
├── TestCase.php                                       # extends Orchestra Testbench
├── Fixtures/
│   ├── Sqlite/sample.sql
│   ├── MySql/sample.sql                               # plan 2
│   └── …
├── Unit/
│   ├── Schema/                                        # tests des DTOs niveau 2
│   ├── Enums/
│   ├── Exceptions/
│   ├── Resolvers/
│   ├── Generator/
│   │   ├── Drivers/
│   │   ├── Renderers/
│   │   ├── Actions/
│   │   ├── Stub/
│   │   └── Writers/
│   ├── Extract/                                       # vide initialement
│   └── Import/                                        # vide initialement
└── Feature/
    ├── Cli/                                           # E2E par commande
    ├── Sqlite/                                        # plan 1
    ├── MySql/                                         # plan 2
    └── …
```

> Les tests de tous les sous-modules vivent au même endroit. C'est plus simple pour la matrice CI et pour appliquer un seuil global de coverage.

---

## 4. Décisions clés (ADR-style)

| # | Décision | Motif |
|---|---|---|
| 1 | Architecture 4 niveaux (`Migration → Modules\Migration → Modules\{G,E,I}`) | Permet le partage de DTOs/Interfaces/Enums au niveau 2 ; isolation des features au niveau 3 ; wrapper niveau 1 pour install simple |
| 2 | Namespace `App\Modules\Migration\` (PSR-4 racine) | Conformité au pattern GSTI (`app/Modules/{Name}/`) ; permet copy/symlink direct sans réécriture |
| 3 | Distribution hybride (Composer + copy) | Couvre les 2 styles d'apps : path-repository simple ou conformité stricte au pattern GSTI |
| 4 | DTOs/Enums partagés au niveau 2 | Évite la duplication (TableSchema utile à G/E/I) ; force une cohérence d'abstraction |
| 5 | Cible PHP 8.4+ et Laravel 13+ | Modernisation, pas de DBAL, Schema Builder natif suffisant |
| 6 | 5 moteurs DB pour Generator | Couverture maximale dès v1.0 (préservé du design précédent) |
| 7 | 3 formats pour Extract/Import + extensible | CSV, JSON, Excel via interfaces `Reader`/`Writer` ; ajout futur trivial |
| 8 | ServiceProvider à chaque niveau | Conformité GSTI + désactivation possible feature par feature |
| 9 | Tests centralisés au niveau 1 (`tests/`) | Simplifie la matrice CI et la mesure du coverage global |
| 10 | Phasage : skeleton → Generator par moteur → Extract → Import | Livre rapidement, valide l'architecture sur 1 sous-feature avant de répéter |

---

## 5. Périmètre de la première phase d'exécution (Plan 1)

### 5.1 Inclus

#### Phase 0 — Squelette complet vide
- composer.json (PSR-4 racine, dépendances v1)
- Outillage : pint.json, phpstan.neon, phpunit.xml, GitHub Actions baseline
- Création de **toute l'arborescence** (4 niveaux × dossiers template) — dossiers vides où il n'y a pas encore de code
- 5 ServiceProviders (1 par module, hiérarchie complète)
- Pack `.md` minimal mais présent (README, CHANGELOG, LICENSE, CONTRIBUTING)

#### Phase 1 — Generator SQLite (v0.1.0-alpha)
Reprend la portée du précédent Plan 1 avec les nouveaux namespaces :
- 3 Enums + 5 DTOs au **niveau 2** (partagés futurs)
- 14 classes d'Exceptions au **niveau 2**
- 2 Resolvers (ColumnType SQLite + DefaultValue) au **niveau 2** (Services/) ou **niveau 3** (Resolvers/) — voir spec détaillée plan
- 3 Renderers (Column, Index, ForeignKey) au **niveau 3 (Generator/Renderers/)**
- Stub system + 2 stubs au **niveau 3**
- SchemaDriver interface au **niveau 2 (Interfaces/)**, AbstractSchemaDriver + SqliteDriver + Factory au **niveau 3**
- 4 Actions au **niveau 3 (Generator/Actions/)**
- GenerateCommand + GeneratorServiceProvider au **niveau 3**
- Tests Feature SQLite + E2E round-trip à `tests/`
- Tag `v0.1.0-alpha`

### 5.2 Exclus de la phase 1

- Drivers MySQL, MariaDB, PostgreSQL, SQL Server (plans 2-5)
- Vues, `--squash`, `--no-foreign-keys`, options legacy (plans ultérieurs)
- Implémentation réelle d'Extract/Import (squelette uniquement, ServiceProviders enregistrés mais commandes en stub)
- Pack `.md` complet (CONTRIBUTING détaillé, UPGRADING détaillé) — minimal pour v0.1
- Publication Packagist publique (réservée v1.0.0)

---

## 6. Stratégie de tests

Reprise des conventions GSTI :
- **Pest 4** + **Orchestra Testbench 11**
- **PHPStan niveau 8** (Larastan)
- **Laravel Pint** (preset Laravel + strict_types)
- **Coverage ≥ 80 %** (gate CI)
- Matrice CI GitHub Actions : PHP 8.4 × Laravel 13 × {SQLite, MySQL 8, MariaDB 11, PostgreSQL 16, SQL Server 2022} (Phase 1 = SQLite seulement, autres ajoutés aux plans suivants)

---

## 7. Documentation

Structure documentaire répartie sur les 4 niveaux :

```
README.md                                              # niveau 1 — overview Migration + roadmap
Docs/INSTALLATION.md                                   # niveau 1 — composer vs copy
Docs/ARCHITECTURE.md                                   # niveau 1 — diagramme architecture
Docs/ROADMAP.md                                        # niveau 1 — phases et versioning

Modules/Migration/Docs/CORE.md                         # niveau 2 — DTOs partagés, interfaces

Modules/Migration/Modules/Generator/Docs/USAGE.md       # niveau 3 — usage migrate:generate
Modules/Migration/Modules/Generator/Docs/DRIVERS.md     # niveau 3 — notes par moteur DB
Modules/Migration/Modules/Extract/Docs/USAGE.md         # niveau 3 — phase ultérieure
Modules/Migration/Modules/Import/Docs/USAGE.md          # niveau 3 — phase ultérieure
```

---

## 8. Critères de succès Phase 0+1

- [ ] Arborescence 4 niveaux complète (dossiers vides présents même quand pas encore utilisés)
- [ ] 5 ServiceProviders enregistrés et fonctionnels (le niveau 1 boote tout transitivement)
- [ ] `composer install` fonctionne dans l'app consommatrice (mode path repository)
- [ ] Symlink `app/Modules/Migration → /var/www/packages/laravel-migrations-generator` fonctionne aussi (mode copy)
- [ ] `php artisan migrate:generate` produit deux fichiers de migration depuis une BD SQLite avec FKs
- [ ] `php artisan migrate` sur ces fichiers reconstruit la BD à l'identique (round-trip)
- [ ] Suite Pest passe à 100 %, coverage ≥ 80 %
- [ ] PHPStan niveau 8 = 0 erreur
- [ ] Pint = 0 warning
- [ ] Tag `v0.1.0-alpha` poussé
