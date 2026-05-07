# Conventions base de données

## Identifiants
- **Clé primaire : UUID v7** (`Str::uuid7()`).
- Colonne : `$table->uuid('id')->primary();`.
- Trait `HasUuidV7` applicable sur Models (génération auto au `creating`).

## Soft deletes par défaut
- **Tous les Models métier** utilisent `SoftDeletes`.
- Colonne `deleted_at NULL` dans la migration : `$table->softDeletes();`.
- Désactivation = décision explicite documentée dans la PR.

## Clés étrangères
- Nommage : `{table_singulier}_id` → `user_id`, `invoice_id`.
- **Contraintes FK explicites** :
  ```php
  $table->foreignUuid('user_id')
      ->constrained()
      ->onUpdate('cascade')
      ->onDelete('restrict');
  ```
- `restrict` par défaut. `cascade` justifié pour relations compositionnelles.

## Index
Obligatoire sur :
- Toutes les clés étrangères.
- Toutes les colonnes dans `WHERE`, `ORDER BY`, `JOIN` fréquents.
- Combos fréquents : `$table->index(['tenant_id', 'status']);`.

Vérification systématique en revue : `EXPLAIN` sur les requêtes critiques.

## Colonnes types
| Donnée                 | Type / Cast                                  |
| ---------------------- | -------------------------------------------- |
| Montant monétaire      | `decimal(12, 2)` + cast `Money` VO           |
| Pourcentage            | `decimal(5, 2)`                              |
| Énumération            | `string` + cast vers Enum PHP 8.4            |
| Texte long             | `text` (> 65k car.)                          |
| Texte court            | `string(255)` par défaut                     |
| JSON                   | `json` + cast `AsArrayObject`/`AsCollection` |

## Migrations
- **Toujours dans le module** : `app/Modules/{Name}/database/migrations/`.
- Chargées via le ServiceProvider (`loadMigrationsFrom`).
- **`down()` obligatoire et réversible.**
- Pas de SQL brut quand Schema existe.
- Nom : `YYYY_MM_DD_HHMMSS_verbe_sujet.php`.

Structure type :
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('restrict');
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
```

## Seeders et Factories par module
- `app/Modules/{Name}/database/{seeders,factories}/`.
- `DatabaseSeeder` racine orchestre les Seeders de modules.
- Factory obligatoire pour chaque Model métier.

## Accès aux données
- Eloquent direct dans Services/Actions pour requêtes simples.
- **Query Object** (dans `app/Modules/{M}/Queries/`) si : > 3 `where/join`, réutilisation > 1 endroit, logique conditionnelle complexe.
- Pas de `DB::select(...)` sauf exception documentée.

## Pagination
- Length-aware par défaut : `->paginate(20)`.
- `per_page` configurable via query string, max 100.
- Enveloppe `{data, meta, links}` via Laravel Resource.
