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

⚠️ **Condition** : l'app consommatrice ne doit **pas** avoir un dossier local `app/Modules/Migration/` qui rentrerait en conflit de namespace.

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
