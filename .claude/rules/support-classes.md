# Classes de base `app/Support/`

Utilisez TOUJOURS ces classes de base dans tout module core ou métier.

## Models de base

### `App\Support\Models\ReferenceModel`
Base pour toute Reference Model (statuts, types, catégories). Gère :
- UUID v7 auto (trait HasUuidV7)
- SoftDeletes
- Colonnes standard (code, label_fr, label_en, description, color, icon, sort_order, is_active, is_system)
- Protection contre suppression des entrées système (is_system=true)
- Cache 1h automatique sur `::byCode()`
- Scope `active()`
- Méthode `label($locale)` multilingue

Voir `rules/reference-models.md` pour la convention complète.

### `App\Support\Models\ModuleConfig`
Base pour tout {Name}Config. Gère :
- UUID v7 auto
- Colonnes standard (key, value JSON, type, group, label_fr, label_en, validation_rules, options, is_editable, is_system, sort_order)
- Protection entrées système
- Cache 1h automatique sur `::get()`
- Casting typé au retour (int/float/bool/string/array)

Voir `rules/module-config.md` pour la convention complète.

## Traits

### `App\Support\Traits\HasUuidV7`
Génère automatiquement un UUID v7 comme clé primaire au `creating`. À appliquer sur **tout Model métier** (déjà inclus dans ReferenceModel et ModuleConfig).

### `App\Support\Traits\Anonymizable`
Permet l'effacement Loi 25 / RGPD. Le Model doit implémenter `anonymizableAttributes(): array`.

## Value Objects

### `App\Support\ValueObjects\Money`
VO pour les montants monétaires :
- Stocké `decimal(12, 2)` + colonne devise séparée en BD
- Opérations via BCmath (précision garantie)
- `add`, `subtract`, `multiply`, `divide`, `equals`, `isGreaterThan`, `isNegative`, `isZero`
- `formatted(locale)` via NumberFormatter PHP

```php
use App\Support\ValueObjects\Money;

$total = Money::of('100.00', 'CAD')
    ->add(Money::of('15.00', 'CAD'))  // TPS
    ->multiply('1.05');                 // +5%
echo $total->formatted('fr_CA');        // "120,75 $ CA"
```

### `App\Support\ValueObjects\Result`
Result pattern pour erreurs métier prévisibles. **Toujours préférer Result à Exception pour les cas attendus** (validation métier, état interdit, concurrence). Exceptions réservées aux erreurs exceptionnelles.

```php
public function publish(Invoice $invoice): Result
{
    if ($invoice->status->code !== InvoiceStatus::CODE_DRAFT) {
        return Result::failure(new InvoiceAlreadyPublishedError($invoice->id));
    }
    $invoice->update(['status_id' => InvoiceStatus::published()->id]);
    return Result::success($invoice);
}

// Consommation
$result = $service->publish($invoice);
if ($result->isFailure()) {
    return response()->json(['error' => $result->error()->getMessage()], 409);
}
return new InvoiceResource($result->value());
```

## Providers

### `App\Support\Providers\ModuleServiceProvider`
**Classe de base obligatoire** pour tout ServiceProvider de module core. Gère :
- Métadonnées du module (version, isCore, dependencies, composerRequires, installAction, uninstallAction, changelogPath)
- Chargement auto des routes, migrations, traductions, vues (conventions GSTI)
- Enregistrement auprès du Module Manager

```php
use App\Support\Providers\ModuleServiceProvider;
use App\Modules\User\Actions\InstallUserModuleAction;

final class UserServiceProvider extends ModuleServiceProvider
{
    public string $moduleCode = 'User';
    public string $version = '1.0.0';
    public bool $isCore = true;
    public array $dependencies = ['Permission', 'Language'];
    public ?string $installAction = InstallUserModuleAction::class;
}
```

## Exceptions

### `App\Support\Exceptions\DomainException`
Base pour toutes les exceptions de domaine métier. Chaque module crée un parent `{Module}DomainException` qui hérite d'elle, puis ses exceptions spécifiques héritent de ce parent.

Hiérarchie :
```
RuntimeException
└── App\Support\Exceptions\DomainException
    └── App\Modules\Facturation\Exceptions\FacturationDomainException
        ├── InvoiceNotFoundException
        ├── InvoiceAlreadyPublishedException
        └── InvalidTaxRateException
```
