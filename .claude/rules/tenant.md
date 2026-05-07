# Multi-tenant (stancl/tenancy)

## Architecture
- **Une base de données par tenant** (database tenancy de stancl/tenancy).
- Résolution du tenant via **JWT/Sanctum token** qui contient `tenant_id`.
- Pas de sous-domaine : URL unique, isolation par auth token.

## Tables dans la BD centrale (host)
- `tenants` (id, code, name, plan_id, is_active, created_at…)
- `tenant_plans` (historique d'assignation Plan ↔ Tenant)
- `users_host` (users globaux : super-admin, admin système)
- `modules` (registre des modules et leur état)

## Tables dans la BD de chaque tenant
Tout le reste : users métier, data métier, module configs, reference models de ce tenant.

## Provisioning
- `php artisan tenant:create {code}` — interactif
- Module Tenant expose un wizard web (super-admin)
- Job asynchrone `ProvisionTenantJob` : crée BD, migrations core, seeders core, premier tenant-admin
- Template de tenant lié au Plan : modules pré-activés + Module Configs par défaut

## Routing
Utiliser les middlewares stancl pour isoler :
```php
Route::middleware(['auth:sanctum', 'tenancy'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});
```

## Queues
Queue dédiée par tenant pour isolation de charge :
```php
$job->onQueue("tenant-{$tenant->id}");
```

## Règles
- Jamais d'accès cross-tenant en runtime (sauf super-admin explicitement)
- Les migrations d'un module doivent fonctionner **par tenant** (pas globalement)
- Le DatabaseSeeder de chaque tenant appelle les Seeders des modules activés pour ce tenant
- Tests Feature : `Tenancy::initialize($tenant)` dans `beforeEach`
