# Module Migration

> Module GSTI hybride pour Laravel 13+ — génération de migrations, extraction et import de données.

[![PHP 8.4+](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://www.php.net)
[![Laravel 13](https://img.shields.io/badge/laravel-13-red.svg)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

⚠️ **v0.0.1-skeleton** — squelette modulaire complet. Generator SQLite arrive en `v0.1.0-alpha`.
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

| Feature | Commande | Statut v0.0.1 |
|---|---|---|
| **Generator** | `php artisan migrate:generate` | 🚧 stub |
| **Extract**   | `php artisan migrate:extract`  | 🚧 stub |
| **Import**    | `php artisan migrate:import`   | 🚧 stub |

## Documentation

- [`Docs/INSTALLATION.md`](Docs/INSTALLATION.md) — installation
- [`Docs/ARCHITECTURE.md`](Docs/ARCHITECTURE.md) — architecture détaillée
- [`Docs/ROADMAP.md`](Docs/ROADMAP.md) — roadmap par phase

## Crédits

Lignée originale : [`xethron/migrations-generator`](https://github.com/Xethron/migrations-generator) par Bernhard Breytenbach. Réécrit et restructuré par [Groupe STI](https://groupesti.com).

## Licence

MIT — voir [LICENSE.md](LICENSE.md).
