# Politique de mot de passe — Rule module Auth

**12 catégories** de validation, inspirées du projet GSTI Prival.PasswordPolicy (C#).
Toutes les valeurs sont configurables via `AuthConfig` (Module Config en BD) et éditables par l'admin.

## Structure obligatoire

```
app/Modules/Auth/
├── PasswordPolicy/
│   ├── Contracts/Validator.php         (interface)
│   ├── ValidationContext.php           (DTO readonly)
│   ├── PolicyViolation.php             (DTO readonly)
│   ├── PasswordPolicyValidator.php     (orchestrateur)
│   ├── Validators/                     (12 implémentations)
│   └── Services/                       (HibpClient, KeyboardPatternDetector, etc.)
├── Models/
│   ├── AuthConfig.php                  (~80 clés)
│   ├── PasswordHistory.php
│   └── BannedPassword.php              (Reference Model avec scope)
├── Rules/PolicyCompliantPassword.php   (Rule Laravel custom)
└── lang/{fr,en}/policy.php
```

## 12 catégories de validation

| # | Catégorie | Groupe AuthConfig | Description |
|---|-----------|-------------------|-------------|
| 1 | Length | `length` | Longueur min/max + plages avec sets requis + expiration par plage |
| 2 | Complexity | `complexity` | Catégories requises (upper/lower/digit/symbol/high/custom) + min_sets |
| 3 | Granular | `granular` | Contraintes fines par type (condition at_least/exactly/at_most × 7 types) |
| 4 | Unique | `unique` | Caractères uniques (mode absolu ou pourcentage) |
| 5 | Age Min | `age_min` | Âge min avant re-changement + extended_days si MDP long |
| 6 | Age Max | `age_max` | Forced change + email reminders 3 paliers + grace_period |
| 7 | Compromised | `compromised` | HIBP k-anonymous + BD locale optionnelle |
| 8 | Dictionary | `dictionary` | Dictionnaires built-in/custom + infos user + variations |
| 9 | History | `history` | N derniers MDP + similarity_threshold + min_days_before_reuse |
| 10 | Patterns | `patterns` | QWERTY/AZERTY/Dvorak + séquences num/alpha |
| 11 | Repetition | `repetition` | Max consécutifs identiques + max patterns répétés |
| 12 | Similarity | `similarity` | % max + Levenshtein + compare à current/username/display/email |

## Règles strictes

### Ne jamais
- ❌ Hardcoder une valeur de politique dans le code métier — toujours lire via `AuthConfig::get('policy.{category}.{key}')`
- ❌ Contourner l'orchestrateur — toute validation passe par `PasswordPolicyValidator`
- ❌ Stocker un mot de passe en clair dans `PasswordHistory` — uniquement le hash (bcrypt/argon2)
- ❌ Envoyer le MDP en clair à HIBP — utiliser **k-anonymous** (SHA1 prefix 5 chars uniquement)
- ❌ Ajouter une nouvelle catégorie sans :
  1. Ajouter le Validator dans `PasswordPolicy/Validators/`
  2. Le tagger dans `AuthServiceProvider::register()` avec `auth.password_policy.validators`
  3. Ajouter ses clés dans `AuthConfigSeeder` avec `is_system=true`
  4. Ajouter les messages dans `lang/{fr,en}/policy.php`

### Toujours
- ✅ `final readonly class` pour les Validators (immuables, injectables)
- ✅ Implémenter `Validator` interface (category, isEnabled, validate)
- ✅ Retourner `Result::success()` ou `Result::failure(PolicyViolation[])` — jamais d'exception pour une règle métier
- ✅ Messages d'erreur via `__('auth::policy.{category}.{code}')` — jamais de string hardcodée
- ✅ Cache 1h sur lectures `AuthConfig::get()` (géré par la classe de base)
- ✅ Tests Pest pour chaque Validator dans `tests/Feature/Modules/Auth/PasswordPolicy/`

## Utilisation dans les FormRequests

```php
use App\Modules\Auth\Rules\PolicyCompliantPassword;

public function rules(): array
{
    return [
        'password' => [
            'required', 'string', 'confirmed',
            new PolicyCompliantPassword(),
        ],
    ];
}
```

## Endpoint live check (password meter)

`POST /api/v1/auth/password/check` avec `{"password": "..."}` retourne :
```json
{
    "data": {
        "valid": false,
        "rules": [
            {"category": "length", "status": "passed", "violation": null},
            {"category": "complexity", "status": "failed", "violation": {...}}
        ]
    }
}
```

Ne pas logguer ni persister le candidat. Rate-limit strict (10/min/user ou IP).

## Ajouter une nouvelle catégorie (rare)

1. Créer `app/Modules/Auth/PasswordPolicy/Validators/MyNewValidator.php` implémentant `Validator`
2. Ajouter la classe dans le tableau tagué de `AuthServiceProvider::register()`
3. Ajouter les clés `policy.my_new.*` dans `AuthConfigSeeder::entries()` (un seeder run suffit)
4. Ajouter messages dans `lang/{fr,en}/policy.php`
5. Ajouter migration + seeder pour éventuelles tables additionnelles (ex: `my_new_blacklist`)
6. Ajouter tests Pest Feature + Unit
7. Ajouter une section dans le Filament admin si édition fine nécessaire

## Pattern "Length Ranges" (spécifique)

La catégorie Length supporte un mode avancé où les règles varient selon la longueur du MDP :

```json
"policy.length.ranges": [
    {"min": 8,  "max": 11, "required_sets": 4, "expiration_days": 30},
    {"min": 12, "max": 15, "required_sets": 3, "expiration_days": 60},
    {"min": 16, "max": 0,  "required_sets": 2, "expiration_days": 90}
]
```

Plus le MDP est long, plus les exigences de complexité diminuent et la durée de vie augmente. C'est une bonne pratique qui **récompense les passphrases**.

Active via `policy.length.use_ranges = true`. Quand activé, `LengthValidator` lit ces ranges et applique les règles selon la longueur du candidat.
