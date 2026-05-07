---
name: password-validator
description: Crée ou modifie un Validator de politique de mot de passe dans le module Auth. Utiliser pour "nouveau validator MDP", "ajouter règle password policy", "implémenter un stub Validator".
---

# Créer / modifier un Password Policy Validator

## Contexte
Le module Auth implémente 12 catégories de politique de MDP. Les 6 plus simples
(Length, Complexity, Unique, Repetition, Patterns, Granular) sont déjà fournies.
Les 7 autres (AgeMin, AgeMax, Compromised, Dictionary, History, Similarity) sont
des **stubs** avec `TODO(GSTI-AUTH)` qu'il faut implémenter.

Voir `.claude/rules/password-policy.md` pour les règles complètes.

## Procédure : implémenter un stub

1. **Ouvrir le stub** dans `app/Modules/Auth/PasswordPolicy/Validators/{Nom}Validator.php`
2. **Lire les clés AuthConfig** correspondantes dans `AuthConfigSeeder::entries()` (groupe `{category}`)
3. **Implémenter la logique** en respectant :
   - `final readonly class` + injection de Services si besoin
   - `category()` retourne le snake_case de la catégorie (ex: `'age_min'`)
   - `isEnabled()` lit `policy.{category}.enabled`
   - `validate(ValidationContext)` retourne `Result::success()` ou `Result::failure(PolicyViolation)`
4. **Messages d'erreur** via `__('auth::policy.{category}.{code}')`. Ajouter dans `lang/{fr,en}/policy.php` si manquant.
5. **Tests Pest** dans `tests/Feature/Modules/Auth/PasswordPolicy/{Nom}ValidatorTest.php` :
   - Cas activé + OK
   - Cas activé + violations
   - Cas désactivé (retourne success sans valider)
   - Edge cases (MDP vide, caractères Unicode, etc.)

## Procédure : créer une nouvelle catégorie

1. Créer `Validators/{Nom}Validator.php` implémentant `Validator`
2. Ajouter la classe dans `AuthServiceProvider::register()` dans le tableau tagué `auth.password_policy.validators`
3. Ajouter les clés dans `AuthConfigSeeder::entries()` (au moins `.enabled` + 1 ou 2 paramètres)
4. Lancer `php artisan db:seed --class="App\Modules\Auth\Database\Seeders\AuthConfigSeeder"`
5. Ajouter messages dans `lang/{fr,en}/policy.php`
6. Ajouter tests Pest

## Checklist avant commit

- [ ] `declare(strict_types=1);` en tête
- [ ] `final readonly class`
- [ ] Implémente `Validator` interface
- [ ] Utilise `AuthConfig::get('policy.{cat}.{key}', default)` pour toutes les valeurs
- [ ] Messages via `__('auth::policy.{cat}.{code}', [...])`
- [ ] Retourne `Result` (jamais d'exception pour erreur métier)
- [ ] PolicyViolation avec `category`, `code`, `message`, optionnellement `context`
- [ ] Tests Pest (Feature + Unit)
- [ ] Tagué dans AuthServiceProvider
- [ ] Pas de hardcoding de valeur dans le code

## Template de test

```php
use App\Modules\Auth\Models\AuthConfig;
use App\Modules\Auth\PasswordPolicy\Validators\{{ Nom }}Validator;
use App\Modules\Auth\PasswordPolicy\ValidationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    AuthConfig::query()->updateOrCreate(
        ['key' => 'policy.{{ category }}.enabled'],
        ['value' => ['v' => true], 'type' => 'bool', 'group' => '{{ category }}', 'label_fr' => '', 'label_en' => ''],
    );
});

it('validates correctly when password meets the requirement', function () {
    $validator = app({{ Nom }}Validator::class);
    $result = $validator->validate(new ValidationContext('GoodPassword123!'));
    expect($result->isSuccess())->toBeTrue();
});

it('rejects when requirement is not met', function () {
    $validator = app({{ Nom }}Validator::class);
    $result = $validator->validate(new ValidationContext('bad'));
    expect($result->isFailure())->toBeTrue();
    expect($result->error())->toBeInstanceOf(PolicyViolation::class);
});
```
