---
name: laravel-module
description: Crée un nouveau module Laravel dans app/Modules/ en respectant l'architecture GSTI. Utiliser quand l'utilisateur demande "créer un module", "nouveau module", "scaffold module".
---

# Créer un nouveau module Laravel

## Étapes
1. Créer la structure : `app/Modules/{PascalCaseName}/{Providers,Models,Database/Migrations,Database/Seeders,Database/Factories,Http/Controllers,Http/Requests,Http/Resources,Http/Middleware,Services,Policies,Concerns,OpenApi,routes,lang/fr,lang/en}`.
   - **PascalCase** pour `Database/Migrations`, `Database/Seeders`, `Database/Factories` (PSR-4 strict).
   - lowercase pour `routes/`, `lang/`, `config/`, `resources/` (Laravel convention).
2. Créer `app/Modules/{Name}/Providers/{Name}ServiceProvider.php` qui hérite de `App\Support\Providers\ModuleServiceProvider` (pas le `ServiceProvider` Laravel direct). Définir `$moduleCode`, `$version`, `$isCore`, `$dependencies`.
3. Enregistrer le provider dans `bootstrap/providers.php`.
4. Créer au minimum :
   - `routes/api.php` (routes du module, préfixe `/api/v1/{name-kebab}`) — les routes sont auto-wrappées dans le middleware `api` par la base provider.
   - `lang/fr/messages.php` + `lang/en/messages.php`
   - **`OpenApi/{Name}Schema.php`** pour chaque Resource exposé (Swagger).
5. Annoter chaque méthode de controller avec `#[OA\Get/Post/Put/Patch/Delete]` — voir `.claude/rules/api-documentation.md`.
6. Si nouveau tag Swagger → l'ajouter dans `app/Modules/ApiDocs/OpenApi/OpenApiInfo.php`.
7. Lancer `composer dump-autoload` pour recharger PSR-4.
8. Régénérer la doc : `php artisan l5-swagger:generate`.
9. Synchroniser le registre : `php artisan module:sync`.

## Vérifications
- [ ] `php artisan route:list --path={name-kebab}` affiche les nouvelles routes
- [ ] `php artisan module:sync` liste le nouveau module
- [ ] Swagger UI à `/api/docs` montre les endpoints du module avec les bons tags
- [ ] Les tests dans `tests/Feature/Modules/{Name}/` passent
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G` — 0 erreur
- [ ] `vendor/bin/pint --test` — passed

## Pattern de ServiceProvider

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Name}\Providers;

use App\Support\Providers\ModuleServiceProvider;

final class {Name}ServiceProvider extends ModuleServiceProvider
{
    public string $moduleCode = '{Name}';

    public string $version = '1.0.0';

    public bool $isCore = false;

    /** @var array<int, string> */
    public array $dependencies = [
        // 'User', 'Permission', etc.
    ];
}
```

La base `ModuleServiceProvider::boot()` charge automatiquement :
- `routes/api.php` (avec middleware `api` appliqué)
- `Database/Migrations/`
- `lang/{fr,en}/`
- `resources/views/`
- `config/{name_snake}.php` (si présent)

## Liens règles
- `.claude/rules/architecture.md` — structure complète d'un module
- `.claude/rules/api-design.md` — format des réponses, Resources, FormRequests
- `.claude/rules/api-documentation.md` — Swagger/OpenAPI obligatoire
- `.claude/rules/reference-models.md` — pour les valeurs énumérées
- `.claude/rules/module-config.md` — pour la config admin-éditable
- `.claude/rules/testing.md` — Pest, RefreshDatabase, factories
