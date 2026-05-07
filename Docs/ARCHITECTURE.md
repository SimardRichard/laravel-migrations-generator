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
