# Conventions de nommage

## Casses
| Élément                  | Casse                | Exemple                    |
| ------------------------ | -------------------- | -------------------------- |
| Namespace                | PascalCase           | `App\Modules\Facturation`  |
| Classe / Interface / Trait | PascalCase         | `InvoiceService`           |
| Méthode                  | camelCase            | `createDraft()`            |
| Variable / propriété     | camelCase            | `$invoiceNumber`           |
| Constante de classe      | SCREAMING_SNAKE_CASE | `MAX_ITEMS = 100`          |
| Enum (cas)               | PascalCase           | `InvoiceStatus::Draft`     |
| Clé de traduction        | snake_case           | `invoice.total_with_taxes` |
| Route name               | dot.snake_case       | `invoices.export_pdf`      |
| URL                      | kebab-case pluriel   | `/api/v1/article-comments` |

## Models et tables
- **Model** : PascalCase singulier → `User`, `Invoice`, `ArticleComment`.
- **Table** : snake_case pluriel → `users`, `invoices`, `article_comments`.
- **Pivot** : snake_case singulier, ordre alphabétique → `role_user`.
- **Clé primaire** : `id` (UUID v7).
- **Clé étrangère** : `{table_singulier}_id` → `user_id`, `invoice_id`.
- **Timestamps** : `created_at`, `updated_at`, `deleted_at`.

## Suffixes obligatoires
| Type                  | Suffixe       | Exemple                      |
| --------------------- | ------------- | ---------------------------- |
| Controller            | `Controller`  | `InvoiceController`          |
| FormRequest           | `Request`     | `StoreInvoiceRequest`        |
| Resource              | `Resource`    | `InvoiceResource`            |
| Resource Collection   | `Collection`  | `InvoiceCollection`          |
| Service               | `Service`     | `InvoiceService`             |
| Action (invokable)    | `Action`      | `CreateInvoiceAction`        |
| DTO                   | `Data`        | `InvoiceData`                |
| Query Object          | `Query`       | `FindOverdueInvoicesQuery`   |
| Policy                | `Policy`      | `InvoicePolicy`              |
| Rule                  | `Rule`        | `ValidSiretRule`             |
| Cast                  | `Cast`        | `MoneyCast`                  |
| Scope                 | `Scope`       | `PublishedScope`             |
| Event                 | (passé simple)| `InvoiceCreated`             |
| Listener              | (verbe+objet) | `SendInvoiceEmail`           |
| Job                   | `Job`         | `GenerateInvoicePdfJob`      |
| Notification          | `Notification`| `InvoiceDueNotification`     |
| Observer              | `Observer`    | `InvoiceObserver`            |
| Exception             | `Exception`   | `InvoiceNotFoundException`   |
| Middleware            | (verbe adj.)  | `EnsureUserIsAdmin`          |
| MCP Tool              | `Tool`        | `GetInvoiceTool`             |

## Verbes de méthodes — sémantique stricte

### Lecture
- `find*` / `findBy*` → **nullable** (`?Model`). Absence possible.
  ```php
  public function findById(string $id): ?Invoice
  ```
- `get*` / `getBy*` → **garantit** la valeur. Lance exception si absent.
  ```php
  public function getById(string $id): Invoice  // throws InvoiceNotFoundException
  ```
- `list*` → retourne Collection / LazyCollection / Paginator.
- `exists*` / `has*` → retourne `bool`.

### Écriture
- `create*` / `update*` / `delete*` / `archive*` / `restore*`.

### Actions métier
Verbe impératif du domaine : `publish()`, `cancel()`, `approve()`, `submit()`.

## Events (passé) et Listeners (impératif)
- **Events** = fait révolu, passé simple anglais :
  - `InvoiceCreated`, `UserLoggedIn`, `OrderShipped`, `PaymentFailed`.
- **Listeners** = action, présent impératif (verbe + objet) :
  - `SendInvoiceEmail`, `NotifyAdminOfFailure`, `UpdateSearchIndex`.
- **1 Listener = 1 Event.** Pas de Listener multi-events.
- Event dans le module **émetteur**, Listener dans le module **consommateur**.

## Reference Models (priorité sur les Enums)
**Par défaut : les valeurs énumérées du domaine métier sont des Reference
Models (tables en BD), pas des Enums PHP.** Motif : permettre l'ajout /
modification / retrait de valeurs via l'interface admin sans déploiement.

Voir `.claude/rules/reference-models.md` pour le détail complet.

- Nom : PascalCase singulier → `InvoiceStatus`, `PaymentMethod`, `OrderType`.
- Table : snake_case pluriel → `invoice_statuses`, `payment_methods`.
- Placement : dans le module concerné (`app/Modules/{M}/Models/`).
- Colonnes standard : `id` (UUID v7), `code` (unique string), `label_fr`,
  `label_en`, `description`, `sort_order`, `is_active`, timestamps.
- Référencement : FK vers la table de référence, pas colonne `string`.
  ```php
  $table->foreignUuid('invoice_status_id')->constrained();
  ```

### Enums PHP : cas résiduels
Les Enums PHP restent autorisés **uniquement** pour des valeurs :
- purement techniques (jamais exposées à l'admin) — ex: `HttpMethod`, `AuthStrategy` ;
- ou totalement immuables par nature — ex: `Weekday`, `Hemisphere`.

Si un doute subsiste, la décision par défaut est **Reference Model**.
Placement Enum : `app/Enums/` (global) ou `app/Modules/{M}/Enums/` (module).

## DTOs
- PascalCase + suffixe `Data` : `InvoiceData`.
- `final readonly class` obligatoire.
- Propriétés publiques readonly en constructor promotion.
- Méthode statique `from(array|Request): self` recommandée.

## Fichiers
- PHP : `{ClassName}.php` (match PSR-4).
- Migrations : `YYYY_MM_DD_HHMMSS_verbe_sujet.php`.
- Factories : `{Entity}Factory.php`.
- Seeders : `{Entity}Seeder.php`.

## Routes
Grouper par module avec préfixe et name :
```php
Route::prefix('api/v1/invoices')->name('invoices.')->group(function () {
    Route::apiResource('/', InvoiceController::class);
    Route::post('{invoice}/publish', [InvoiceController::class, 'publish'])->name('publish');
});
```
