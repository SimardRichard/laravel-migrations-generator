---
name: code-review
description: Revue de code avant commit/PR. Utiliser pour "revoir ce code", "review my changes".
---

# Revue de code

## Pipeline automatisée
```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
./vendor/bin/pest --coverage --min=80
composer audit
```

## Checklist manuelle
### Architecture
- [ ] Code dans `app/Modules/{Name}/` (pas en racine app/)
- [ ] Pas de dépendance directe inter-modules
- [ ] `declare(strict_types=1);` partout
- [ ] final sur Services/Actions/DTOs

### Patterns projet
- [ ] Reference Models utilisés (pas d'Enum pour valeurs admin-éditables)
- [ ] Module Config utilisé (pas de config/*.php pour valeurs métier)
- [ ] UUID v7 sur les clés primaires
- [ ] SoftDeletes sur Models métier
- [ ] FK avec onDelete/onUpdate explicites
- [ ] Index sur FK et colonnes filtrées

### Sécurité
- [ ] FormRequest pour toute entrée utilisateur
- [ ] Policy Spatie Permission sur endpoints protégés
- [ ] Pas de SQL brut non paramétré
- [ ] Pas de secret committé

### Qualité
- [ ] Tests Feature (anglais) pour chaque endpoint
- [ ] DocBlock systématique sur classes publiques
- [ ] Events au passé, Listeners à l'impératif
- [ ] Commits Conventional Commits

### Documentation API
- [ ] Tout nouveau controller a des attributs `#[OA\Get/Post/Put/Patch/Delete]` sur chaque méthode
- [ ] Tout nouveau Resource a son schema dans `app/Modules/{Module}/OpenApi/`
- [ ] Endpoints protégés déclarent `security: [['sanctum' => []]]`
- [ ] Réponses 422 utilisent `#/components/schemas/ValidationError`
- [ ] Listes paginées utilisent `PaginationMeta` + `PaginationLinks`
- [ ] `php artisan l5-swagger:generate` exécuté sans erreur — voir `.claude/rules/api-documentation.md`
