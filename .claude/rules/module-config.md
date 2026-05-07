# Configuration modulaire — Model Config en BD

## Décision
**La configuration métier d'un module vit dans un `{Name}Config` Model en BD,
pas dans un fichier `config/*.php`.** Motif : rendre toute la configuration
modifiable via l'interface admin sans déploiement.

### Ce qui VA en BD (`{Name}Config` Model)
- Paramètres métier, seuils, limites (ex: `default_tax_rate`, `max_invoice_amount`)
- Toggles de features (ex: `enable_pdf_export`, `require_approval`)
- Valeurs par défaut des formulaires
- URL / tokens / endpoints d'intégrations partenaires
- Délais, TTLs, fenêtres temporelles métier
- Templates d'emails / notifications (sujet, corps)
- Préférences d'affichage par tenant

### Ce qui RESTE dans `config/*.php` ou `app/Modules/{M}/config/*.php`
**Uniquement** ce qui est résolu au bootstrap Laravel et ne peut pas être en BD :
- Bindings DI (`container->singleton(...)`)
- Providers enregistrés
- Middleware aliases
- Chemins de fichiers framework
- Drivers (cache, queue, db connection names)
- Valeurs de fallback ultimes si la BD est indisponible

**Règle simple :** si un utilisateur/admin pourrait vouloir le modifier un jour
sans déployer → **Model Config en BD**. Sinon → fichier PHP.

## Structure standard

### Table (migration par module)
```php
Schema::create('facturation_configs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('key')->unique();            // ex: 'default_tax_rate'
    $table->json('value');                      // typée via colonne 'type'
    $table->string('type');                     // 'string'|'int'|'float'|'bool'|'array'|'json'
    $table->string('group')->default('general');// pour regrouper dans l'UI admin
    $table->string('label_fr');
    $table->string('label_en');
    $table->text('description')->nullable();
    $table->json('validation_rules')->nullable();  // règles Laravel à appliquer
    $table->json('options')->nullable();        // choix (si enum-like), min/max, etc.
    $table->boolean('is_editable')->default(true);
    $table->boolean('is_system')->default(false); // protégé contre suppression
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index(['group', 'sort_order']);
});
```

### Model du module
```php
<?php

declare(strict_types=1);

namespace App\Modules\Facturation\Models;

use App\Support\Models\ModuleConfig;

/**
 * Configuration du module Facturation.
 *
 * Valeurs gérables via l'interface admin. Les entrées système
 * (is_system=true) sont protégées contre la suppression.
 */
final class FacturationConfig extends ModuleConfig
{
    // Clés fréquemment utilisées — constantes pour référence stable
    public const KEY_DEFAULT_TAX_RATE = 'default_tax_rate';
    public const KEY_INVOICE_NUMBER_PREFIX = 'invoice_number_prefix';
    public const KEY_ENABLE_PDF_EXPORT = 'enable_pdf_export';
    public const KEY_PAYMENT_DUE_DAYS = 'payment_due_days';

    protected $table = 'facturation_configs';
}
```

### Classe de base `ModuleConfig` (`app/Support/Models/ModuleConfig.php`)
```php
<?php

declare(strict_types=1);

namespace App\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

abstract class ModuleConfig extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key', 'value', 'type', 'group',
        'label_fr', 'label_en', 'description',
        'validation_rules', 'options',
        'is_editable', 'is_system', 'sort_order',
    ];

    protected $casts = [
        'value' => 'array',         // wrapper JSON — décodé via getValue()
        'validation_rules' => 'array',
        'options' => 'array',
        'is_editable' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= Str::uuid7()->toString());

        static::deleting(function ($m) {
            if ($m->is_system) {
                throw new \RuntimeException("Cannot delete system config: {$m->key}");
            }
        });

        static::saved(fn ($m) => static::forgetCache($m->key));
    }

    /** Lit une valeur typée. Cache 1 heure. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = static::class . ":key:{$key}";

        $row = Cache::remember(
            $cacheKey,
            now()->addHour(),
            fn () => static::query()->where('key', $key)->first(),
        );

        if (! $row) {
            return $default;
        }

        return static::castValue($row->value, $row->type);
    }

    /** Écrit une valeur. Invalide le cache. */
    public static function set(string $key, mixed $value): void
    {
        $row = static::query()->where('key', $key)->firstOrFail();

        if (! $row->is_editable) {
            throw new \RuntimeException("Config is not editable: {$key}");
        }

        $row->update(['value' => ['v' => $value]]);
        static::forgetCache($key);
    }

    public static function forgetCache(string $key): void
    {
        Cache::forget(static::class . ":key:{$key}");
    }

    protected static function castValue(array $wrapped, string $type): mixed
    {
        $v = $wrapped['v'] ?? null;

        return match ($type) {
            'int'    => (int) $v,
            'float'  => (float) $v,
            'bool'   => (bool) $v,
            'string' => (string) $v,
            'array', 'json' => $v,
            default  => $v,
        };
    }
}
```

## Usage

### Lecture
```php
$taxRate = FacturationConfig::get(FacturationConfig::KEY_DEFAULT_TAX_RATE, 0.15);
$prefix = FacturationConfig::get('invoice_number_prefix', 'INV-');

if (FacturationConfig::get('enable_pdf_export', false)) {
    // …
}
```

### Écriture (typiquement depuis Controller admin)
```php
FacturationConfig::set('default_tax_rate', 0.1475);
```

### Seeder obligatoire (valeurs système par défaut)
```php
final class FacturationConfigSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'key' => 'default_tax_rate',
                'value' => ['v' => 0.15],
                'type' => 'float',
                'group' => 'taxes',
                'label_fr' => 'Taux de taxe par défaut',
                'label_en' => 'Default tax rate',
                'description' => 'Appliqué aux nouvelles factures sans taux explicite.',
                'validation_rules' => ['numeric', 'min:0', 'max:1'],
                'is_system' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'invoice_number_prefix',
                'value' => ['v' => 'INV-'],
                'type' => 'string',
                'group' => 'numbering',
                'label_fr' => 'Préfixe numéro de facture',
                'label_en' => 'Invoice number prefix',
                'validation_rules' => ['string', 'max:10'],
                'is_system' => true,
                'sort_order' => 20,
            ],
            // …
        ];

        foreach ($entries as $data) {
            FacturationConfig::updateOrCreate(['key' => $data['key']], $data);
        }
    }
}
```

## Cache
- `Cache::remember(...)` 1 heure par défaut dans `ModuleConfig::get()`.
- Invalidation automatique au `save` / `set`.
- En staging/prod : driver Redis.

## Administration (interface web)
CRUD admin standard par module :
- `GET /api/v1/admin/modules/{module}/configs` — liste groupée par `group`
- `PUT /api/v1/admin/modules/{module}/configs/{key}` — modification
- Les entrées `is_system=true` non supprimables, mais **éditables** si `is_editable=true`
- La validation du `value` utilise `validation_rules` stockées dans la ligne

Policy : réservé aux rôles admin via Spatie Permission.

## Interaction avec `config()` Laravel
**Ne pas** exposer `ModuleConfig` via le système `config()` Laravel. Raisons :
- `config()` est caché au boot (`config:cache`) et figé par requête.
- Incompatible avec invalidation en temps réel.
- Pourrait confondre dev/ops sur la source de vérité.

Accès **toujours** via le Model : `FacturationConfig::get(...)`.

## Anti-patterns interdits
- ❌ `config('facturation.default_tax_rate')` pour valeur gérable en admin
- ❌ `env('DEFAULT_TAX_RATE')` pour valeur métier
- ❌ Hardcoder une valeur métier dans un Service
- ❌ Appels directs BD sans cache dans une boucle (utiliser `get()` qui cache)
