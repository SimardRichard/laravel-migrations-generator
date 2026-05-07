---
name: extend-core-module
description: Étend un module core existant (ajoute une entité/endpoint dans User, Auth, Profile, etc.). Utiliser pour "ajouter un endpoint dans le module X", "étendre le module core".
---

# Étendre un module core

Règles lorsque vous ajoutez quelque chose dans un module core (User, Profile, Auth, etc.) :

## Ne jamais casser le core
- Garder `is_core=true`, les migrations compatibles ascendantes
- Toute nouvelle colonne sur les tables core = nouvelle migration **additive** (jamais `renameColumn` ou `dropColumn` sans plan de migration versionné)
- Incrémenter `version` du ServiceProvider (semver)

## Pattern : ajouter une fonctionnalité dans un core module
1. Créer une nouvelle migration `add_X_to_{table}_table` (pas `update`) dans `Database/Migrations/`
2. Ajouter les FormRequests/Resources/Controllers (nouvelles classes, pas modification)
3. Annoter chaque méthode controller avec `#[OA\Get/Post/Put/Patch/Delete]` — voir `.claude/rules/api-documentation.md`
4. Si la nouvelle réponse n'est pas représentable par un schema existant → créer/étendre `app/Modules/{Module}/OpenApi/`
5. Enregistrer les nouvelles routes dans `routes/api.php` du module (le middleware `api` est appliqué automatiquement)
6. Ajouter un CHANGELOG.md entry
7. Ajouter tests Pest
8. Régénérer la doc : `php artisan l5-swagger:generate`

## Pattern : ajouter une dépendance d'un module core vers un autre
- Vérifier l'ordre de bootstrap dans `bootstrap/providers.php`
- Ajouter le code dans `public array $dependencies` du ServiceProvider
- Si c'est une dépendance circulaire potentielle : passer par Events/Interfaces

## Interdictions
- ❌ Modifier directement un Model core (User, Profile) pour ajouter du métier
  → créer une extension polymorphique ou un nouveau module métier
- ❌ Retirer une colonne core utilisée par d'autres modules
- ❌ Modifier le contrat d'une Interface core sans bump major

## Pour des besoins métier spécifiques à un projet
Créer un **nouveau module** qui étend via des relations plutôt que modifier le core.
Ex: au lieu d'ajouter `tax_number` sur `user_profiles`, créer `app/Modules/Facturation/Models/BillingProfile` en 1-1 avec User.
