# Contexte du projet : .

## Stack (figée)
- PHP 8.4+ / Laravel 13 (API-first, /api/v1)
- MySQL 9+ avec UUID v7
- Pest (coverage > 80%) / Pint / Larastan niveau 8
- Spatie Permission + Policies
- Laravel Sanctum (auth par défaut, JWT en option)
- MCP via `laravel/mcp`
- Architecture modulaire sous `app/Modules/`
- Reference Models + Module Config en BD (tout éditable via admin)

## Environnements
- **local** : dev individuel (.env.testing pour Pest)
- **staging** : (URL à définir), config:cache forcé
- **production** : (URL à définir), config:cache forcé

## Équipe
- Propriétaire technique : (à compléter)
- Propriétaire produit : (à compléter)
