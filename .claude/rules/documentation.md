# Documentation — règles de maintenance

> **Règle d'or :** chaque artefact doit avoir sa documentation. Si elle n'existe pas, on la crée. Si quelque chose change, la doc change avec.

## Niveaux de documentation

### 1. Racine du projet — `README.md` (à la racine `/var/www/{Name}/`)

Doit contenir :
- Présentation 1-2 phrases du projet (Postelio = mail SaaS multi-tenant)
- Stack technique (PHP, Laravel, MySQL, Redis, Stalwart)
- Démarrage rapide (clone, composer install, .env, migrate)
- Liens vers `.claude/` pour les conventions
- Liens vers `docs/` pour les specs/plans
- Liste des modules installés (référence vers chaque README de module)

### 2. Module — `app/Modules/{Name}/README.md`

**Obligatoire pour tout module.** Doit contenir :

```markdown
# Module {Name}

> Une phrase qui décrit ce que fait le module.

**Statut :** ✅ Mergé (Plan X.Y) | 🔨 En cours | 📋 Planifié
**Version :** 1.0.0
**isCore :** true | false
**Dependencies :** [Core, Tenant, ...]

## Périmètre

Quelques bullets de ce que le module FAIT.

## Hors scope (volontaire)

Quelques bullets de ce qu'il NE fait PAS et pourquoi.

## Composants principaux

Liste des Models / Services / Actions / Jobs / Commands / Endpoints API.

## Schéma data

Tables host créées + tables tenant créées (s'il y en a).

## Endpoints API

| Méthode | Path | Auth | Description |
|---------|------|------|-------------|

## Commandes Artisan

| Commande | Description |
|----------|-------------|

## Events dispatchés

Liste des events que le module dispatche (pour Audit + écoute par autres modules).

## Tests

Nombre de tests Pest, où ils sont (`tests/Feature/Modules/{Name}/`).

## Plans liés

- Spec : `docs/superpowers/specs/YYYY-MM-DD-planX.Y-{name}-design.md`
- Plan : `docs/superpowers/plans/YYYY-MM-DD-planX.Y-{name}.md`

## Notes
```

### 3. Architecture globale — `docs/architecture.md`

Document **vivant** qui décrit :
- Vue d'ensemble du système (mail Stalwart + Laravel multi-tenant + DNS provider Bind/GoDaddy)
- Diagramme ASCII des services (mail server, Laravel app, MySQL, Redis, Bind9, GoDaddy)
- Schéma data global (host DB tables + tenant DB tables)
- Flux principaux (réception mail, provisioning tenant, login, etc.)
- Liste des modules avec leur rôle
- Décisions architecturales clés (DB-per-tenant, Sanctum, Spatie, etc.)

### 4. Mémoire Claude — `/var/www/.claude/projects/-var-www-postelio-ca/memory/laravel-modules.md`

Rapide synthèse pour les sessions Claude futures :
- Tableau des modules installés (Module / Version / isCore / Plan / Statut)
- Conventions clés (très synthétique)
- Commandes utiles

### 5. `.claude/` — convention spécifique

Le dossier `.claude/` est le **squelette de conventions** repris d'un autre projet (CRASSQ). Il contient :
- `rules/` — règles d'architecture, naming, sécurité (à respecter strictement)
- `templates/` — stubs pour créer rapidement des fichiers conformes
- `skills/` — skills personnalisés Claude Code
- `agents/`, `commands/`, `scripts/` — automation
- `agents-memory/` — mémoire long-terme partagée par les agents
- `module-registry.md` — référence (vient de CRASSQ, à adapter pour Postelio quand pertinent)

Pour la documentation de Postelio spécifiquement, voir :
- `app/Modules/{Name}/README.md` pour chaque module
- `docs/architecture.md` pour l'architecture
- `docs/superpowers/specs/` et `docs/superpowers/plans/` pour l'historique des décisions

## Quand mettre à jour ?

| Action | Doc à mettre à jour |
|--------|---------------------|
| Créer un module | README module + `docs/architecture.md` (liste modules) + `MEMORY.md` Claude |
| Modifier les endpoints d'un module | README module section "Endpoints API" |
| Ajouter une commande artisan | README module section "Commandes Artisan" |
| Ajouter un Event | README module section "Events dispatchés" |
| Ajouter une migration | README module "Schéma data" si table nouvelle |
| Bumper version (breaking change) | README + CHANGELOG.md du module si présent |
| Changer architecture (multi-tenant, queue, etc.) | `docs/architecture.md` |
| Changer une règle | `.claude/rules/{rule}.md` |
| Décider quelque chose d'important | `.claude/agents-memory/module-architect.md` (ADR section) |

## Anti-patterns interdits

- ❌ Module sans README (refus de merge)
- ❌ Endpoint API ajouté sans mise à jour du README "Endpoints API"
- ❌ Doc figée qui mentionne des classes/tables qui n'existent plus
- ❌ Faire de la doc dans le commit message uniquement (le commit raconte le QUOI, le README explique le COMMENT et le POURQUOI)
- ❌ Commits "docs" séparés des commits "feat" — on commit la doc avec le code qu'elle documente

## Convention de placement

```
/var/www/{Name}/
├── README.md                            ← root project
├── docs/
│   ├── architecture.md                  ← architecture vivante
│   └── superpowers/
│       ├── specs/                       ← brainstorming → design validé
│       ├── plans/                       ← spec → étapes d'exécution
│       └── state/                       ← snapshots / handoffs
├── app/Modules/{Name}/
│   └── README.md                        ← doc du module
└── .claude/
    └── rules/                           ← conventions globales (dont celle-ci)
```

## Pour les agents Claude Code

**Mise à jour en TEMPS RÉEL — pas en batch à la fin :**

1. **Pendant l'implémentation** : chaque fois qu'on ajoute un endpoint / commande / event / table → on met à jour le README du module **dans le même commit** que le code
2. **À la création d'un module** : README module créé en MÊME TEMPS que le ServiceProvider (premier commit du module)
3. **Architecture change** : `docs/architecture.md` mis à jour dans le commit qui change l'archi
4. **Mémoire Claude** : `MEMORY.md` + memoire spécifique mis à jour quand le merge final arrive (un seul commit doc à la fin du plan suffit pour la mémoire)
5. **README racine** : mis à jour quand un nouveau module arrive ou quand un changement majeur affecte le setup global

Anti-patterns :
- ❌ "On documentera à la fin" (= jamais)
- ❌ Commit "feat: add X" sans toucher au README
- ❌ README qui mentionne des routes/commandes qui n'existent plus
