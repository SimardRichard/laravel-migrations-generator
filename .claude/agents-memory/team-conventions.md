# Conventions d'équipe GSTI

## Langue
- Code/identifiants : anglais
- DocBlocks/commentaires : français
- Tests Pest : anglais (it('creates...'))
- UI/traductions : fr primaire, en fallback

## Workflow
- PR obligatoires, pas de push direct sur main
- 1 approbation minimum
- Squash merge par défaut
- Conventional Commits
- CI : Pint + Larastan + Pest --min=80 + composer audit
