# Conventions Git

## Format des commits (Conventional Commits)
```
<type>(<scope>): <description impératif>

[corps optionnel expliquant le pourquoi]

[footer : Refs #123, BREAKING CHANGE:…]
```

## Types autorisés
- `feat` : nouvelle fonctionnalité
- `fix` : correction de bug
- `refactor` : refactorisation sans changement de comportement
- `perf` : amélioration de performance
- `test` : ajout/modification de tests
- `docs` : documentation
- `chore` : tâches de maintenance (deps, config, etc.)
- `ci` : configuration CI/CD

## Exemples
```
feat(facturation): ajouter l'export PDF des factures
fix(auth): corriger la regénération du token après logout
refactor(modules): extraire le service de notification dans Support
```

## Branches
- `main` : production
- `develop` : intégration
- `feature/<nom-court>` : nouvelles features
- `fix/<nom-court>` : corrections
- `hotfix/<nom-court>` : correctifs urgents prod
