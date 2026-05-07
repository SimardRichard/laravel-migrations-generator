# Architecture du projet — MODULAIRE OBLIGATOIRE

> **Règle d'or : toute programmation est modulaire. Aucune exception.**
> Chaque feature, endpoint, model, service, job, event, policy, MCP tool
> **doit** vivre dans un module sous `app/Modules/{ModuleName}/`.

## Où va quel code ?

### ✅ `app/Modules/{ModuleName}/` — TOUT le code métier
Chaque module est une application Laravel autonome, calquée sur la même
arborescence que `app/` :
```
app/Modules/Facturation/
├── Casts/               Classes/              Console/Commands/
├── Constants/           DTOs/                 Enums/
├── Exceptions/          Facades/              Helpers/
├── Http/
│   ├── Controllers/     Middleware/           Requests/     Resources/
├── Interfaces/          Jobs/Middleware/      Listeners/
├── Mail/
├── Mcp/
│   ├── Prompts/         Resources/            Servers/      Tools/
├── Models/Scopes/       Modules/              Notifications/
├── Observers/           Policies/             Providers/
├── Rules/               Services/             Support/      Traits/
├── View/Components/
├── config/              database/{factories,migrations,seeders}/
├── lang/{en,fr}/
├── resources/
│   ├── audio/ css/ image/ js/ json/ scss/ video/
│   └── views/{components,errors,mail,mcp,layouts,shared,vendor}/
└── routes/
```

### ⚠️ `app/` (racine) — uniquement l'infrastructure partagée
Les dossiers racines de `app/` (`app/Http/Controllers`, `app/Models`,
`app/Services`, etc.) sont réservés à **du code transversal réellement
partagé par tous les modules** :
- `app/Http/Controllers/Controller.php` — base controller Laravel
- `app/Http/Middleware/` — middlewares globaux
- `app/Providers/AppServiceProvider.php` — bootstrap framework
- `app/Exceptions/Handler.php` — handler global
- `app/Console/Kernel.php` — scheduler
- `app/Mcp/Servers/*` — serveur MCP "racine" qui agrège ceux des modules

**Si tu hésites → c'est un module.** Un Controller métier, un Model,
un Service, une Policy — jamais en racine. Toujours dans un module.

## Règles strictes

### 1. Isolation des modules
- **Aucune dépendance directe entre modules.**
  Passer par : Events + Listeners, Interfaces (contrat dans un module
  "partagé" et implémentation dans l'appelant), Facades, ou DTOs publics.
- Un module doit pouvoir être désactivé (provider commenté) sans casser
  les autres — sinon le couplage est illégal.

### 2. ServiceProvider obligatoire
Chaque module a `app/Modules/{Name}/Providers/{Name}ServiceProvider.php`
enregistré dans `bootstrap/providers.php`. Le provider doit charger :
```php
$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
$this->loadTranslationsFrom(__DIR__ . '/../lang', '{name_snake}');
$this->loadViewsFrom(__DIR__ . '/../resources/views', '{name_snake}');
$this->mergeConfigFrom(__DIR__ . '/../config/{name_snake}.php', '{name_snake}');
```

### 3. Namespacing PSR-4
`composer.json` → `autoload.psr-4` :
```json
"App\\Modules\\": "app/Modules/"
```
Tous les namespaces d'un module : `App\Modules\{Name}\…`. Jamais de classe
métier dans `App\` directement.

### 4. Couches internes au module (MVC+)
Dans **chaque** module, respecter l'ordre :
```
Controller  →  FormRequest (validation)
            →  Service / Action (logique métier, transactions)
            →  Model (persistance uniquement)
            →  Resource (transformation réponse API)
```
- **Pas** de logique métier dans les Models (uniquement scopes, casts, relations).
- **Pas** de logique métier dans les Controllers (uniquement orchestration).
- DTOs pour transporter entre Service et Controller quand les tableaux
  deviennent flous.

### 5. Sous-modules
Un module peut contenir ses propres sous-modules via `app/Modules/{Parent}/Modules/{Child}/`.
Même règle : provider dédié, isolation complète.

### 6. MCP modulaire
- Tools MCP d'un module → `app/Modules/{Name}/Mcp/Tools/`
- Enregistrement via le provider du module
- Le serveur MCP "racine" (`app/Mcp/Servers/`) agrège les tools exposés
  par les modules actifs

### 7. Ressources et traductions
- Assets d'un module → `app/Modules/{Name}/resources/`
- Traductions → `app/Modules/{Name}/lang/{fr,en}/` avec namespace
  (`trans('facturation::messages.saved')`)
- Vues → `app/Modules/{Name}/resources/views/` avec namespace
  (`view('facturation::invoice.show')`)

### 8. Configuration du module — Model en BD
- La configuration **métier** d'un module vit dans `{Name}Config` Model (BD),
  **pas** dans un fichier `config/*.php`. Voir `.claude/rules/module-config.md`.
- Le dossier `app/Modules/{Name}/config/` reste utilisé uniquement pour des
  bindings framework (DI, aliases, drivers) qui doivent être résolus au boot.
- Accès métier : toujours `{Name}Config::get('key', default)`, jamais `config(...)`.

### 9. Valeurs énumérées — Reference Models en BD
- Les valeurs énumérées du domaine (status, types, catégories) sont des
  **Reference Models** en BD, pas des Enums PHP. Voir `.claude/rules/reference-models.md`.
- Enums PHP réservés aux valeurs purement techniques ou absolument immuables.

## Anti-patterns interdits
- ❌ `app/Http/Controllers/InvoiceController.php` (métier en racine)
- ❌ `app/Models/Invoice.php` (model métier en racine)
- ❌ `App\Modules\Invoicing\Services\X` appelle `App\Modules\Billing\Services\Y` en direct
- ❌ Migration métier dans `database/migrations/` (hors racine du module)
- ❌ Route métier dans `routes/api.php` racine
- ❌ ServiceProvider qui touche plusieurs modules
