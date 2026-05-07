# Standards de code PHP — Référence principale

> Voir aussi : `naming-conventions.md`, `database.md`, `testing.md`, `api-design.md`.

## Obligatoire sur tout fichier PHP
- `declare(strict_types=1);` en tête de **tous** les fichiers PHP (sauf config/routes).
- Typage explicite : paramètres, retours, propriétés. `mixed` interdit sauf justification.
- PSR-12 via Laravel Pint (préréglage `laravel` + overrides dans `pint.json`).
- Constructor property promotion + `readonly` quand initialisée au constructeur.
- `final` obligatoire sur : Services, Actions, DTOs, Exceptions custom, Value Objects.
- `final` NON imposé sur : Models Eloquent, Controllers.
- DocBlock **systématique** sur tout symbole public.

## Immuabilité
- Services / Actions / DTOs → `final` + propriétés `readonly`.
- Models Eloquent → non `final` (Laravel les étend).
- Value Objects → `final readonly class` (PHP 8.4).

## Gestion des erreurs — Exceptions + Result pattern
Deux mécanismes complémentaires :

- **Exceptions typées** pour erreurs exceptionnelles non prévues métier :
  - Une par cause racine, dans `app/Modules/{M}/Exceptions/`.
  - Hérite de `{Module}DomainException` (lui-même `\RuntimeException`).
  - Handler global `app/Exceptions/Handler.php` → JSON structuré.
- **Result pattern** pour erreurs métier prévisibles (validation, état) :
  ```php
  public function publish(Invoice $invoice): Result
  {
      if ($invoice->status !== InvoiceStatus::Draft) {
          return Result::failure(new InvoiceAlreadyPublishedError($invoice->id));
      }
      return Result::success($invoice);
  }
  ```

## Commentaires
- Expliquent le **POURQUOI**, pas le QUOI.
- Phrase d'intro française en DocBlock au-dessus de **chaque classe publique**.
- `TODO` / `FIXME` **interdits sans ticket** : `// TODO(GSTI-123): …`.
- Fichiers > 100 lignes : utiliser des blocs de section pour regrouper :
  ```php
      // ═════════════════════════════════════════════
      // Cycle de vie
      // ═════════════════════════════════════════════
  ```

## Interdits
- `@var`/`@param`/`@return` redondants quand le type PHP natif suffit.
- `env()` hors des fichiers `config/*.php`.
- Logique métier dans les Models Eloquent (uniquement relations, scopes, casts, accessors).
- Requêtes N+1 (utiliser `with()`/`load()` systématiquement).
- Appel direct à un autre module via son namespace (Events/Interfaces/Facades uniquement).
- `Log::info(...)` direct pour événements métier → Event + Listener.

## Helpers globaux
Autorisés **uniquement dans `app/Helpers/`** (ou `app/Modules/{M}/Helpers/`).
Chargés via `composer.json` → `autoload.files`.
Réserver aux utilitaires **vraiment globaux** ; préférer méthodes statiques sur une classe dédiée.

## Logging
- Log JSON structuré (Monolog `JsonFormatter`).
- Contexte obligatoire : `user_id`, `request_id`, `module`.
- Middleware global ajoute le contexte à chaque requête.
- Événements métier : **Event → Listener → Log**, jamais `Log::info()` direct dans un Service.
