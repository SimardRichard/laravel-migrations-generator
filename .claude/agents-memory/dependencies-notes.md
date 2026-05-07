# Notes sur les dépendances

## Règle d'or
Jamais de beta/dev. Composer stable-only, npm sans canary/rc.

## Dépendances principales
- laravel/framework ^13.0
- laravel/mcp — MCP officiel
- laravel/sanctum — auth API
- spatie/laravel-permission — rôles/permissions
- pestphp/pest + pestphp/pest-plugin-laravel
- larastan/larastan — niveau 8
- laravel/pint — formatage

## Upgrades
1. `.claude/scripts/update-deps.sh`
2. `.claude/scripts/test-all.sh`
3. Si rouge : branche `chore/deps-YYYYMMDD`
