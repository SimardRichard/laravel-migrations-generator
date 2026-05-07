# Reference Models — pattern obligatoire pour données énumérées

## Décision
**Par défaut, toute valeur énumérée du domaine métier est un Reference Model
(table en BD), pas un Enum PHP.**

### Raisons
- Permettre la **modification via interface admin** sans déploiement.
- Ajouter de nouvelles valeurs sans code change.
- Attacher des métadonnées riches (description, couleur, icône, ordre, i18n).
- Désactiver une valeur sans la supprimer (`is_active = false`).

### Quand utiliser un Enum PHP à la place
Uniquement pour des valeurs :
- purement techniques, jamais exposées à l'admin (ex: `HttpMethod`, `AuthStrategy`) ;
- totalement immuables par nature (ex: `Weekday`, `Hemisphere`).

## Structure standard d'une Reference Model

### Table (migration)
```php
Schema::create('invoice_statuses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('code')->unique();           // identifiant machine stable
    $table->string('label_fr');                 // libellé français
    $table->string('label_en');                 // libellé anglais
    $table->text('description')->nullable();
    $table->string('color', 7)->nullable();     // #RRGGBB pour UI
    $table->string('icon')->nullable();         // nom icône
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_system')->default(false); // protégé contre suppression
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_active', 'sort_order']);
});
```

### Model
```php
<?php

declare(strict_types=1);

namespace App\Modules\Facturation\Models;

use App\Support\Models\ReferenceModel;

/**
 * Statuts possibles d'une facture (table de référence).
 *
 * Valeurs gérables via l'interface admin. Les valeurs "système"
 * (is_system=true) sont protégées contre la suppression.
 */
final class InvoiceStatus extends ReferenceModel
{
    // Constantes de codes pour référence programmatique stable
    public const CODE_DRAFT = 'draft';
    public const CODE_PUBLISHED = 'published';
    public const CODE_CANCELLED = 'cancelled';
    public const CODE_PAID = 'paid';

    protected $table = 'invoice_statuses';

    public static function draft(): self
    {
        return self::byCode(self::CODE_DRAFT);
    }

    public static function published(): self
    {
        return self::byCode(self::CODE_PUBLISHED);
    }
}
```

### Classe de base `ReferenceModel` (dans `app/Support/Models/`)
```php
<?php

declare(strict_types=1);

namespace App\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class ReferenceModel extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code', 'label_fr', 'label_en', 'description',
        'color', 'icon', 'sort_order', 'is_active', 'is_system',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= Str::uuid7()->toString());

        // Protège les entrées système contre la suppression
        static::deleting(function ($m) {
            if ($m->is_system) {
                throw new \RuntimeException("Cannot delete system reference: {$m->code}");
            }
        });
    }

    /** Résout une référence par son code. Cache en mémoire dans la requête. */
    public static function byCode(string $code): static
    {
        return static::query()->where('code', $code)->firstOrFail();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function label(string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return $this->{"label_{$locale}"} ?? $this->label_fr;
    }
}
```

## Usage

### Relations
```php
// Invoice model
public function status(): BelongsTo
{
    return $this->belongsTo(InvoiceStatus::class);
}
```

### Migration FK
```php
$table->foreignUuid('invoice_status_id')
    ->constrained('invoice_statuses')
    ->onUpdate('cascade')
    ->onDelete('restrict');
```

### Vérifications de code (équivalent du `match` enum)
```php
// ❌ Mauvais : comparer sur label (peut changer)
if ($invoice->status->label_fr === 'Publié') { … }

// ✅ Bon : comparer sur code (stable)
if ($invoice->status->code === InvoiceStatus::CODE_PUBLISHED) { … }

// ✅ Bon aussi : helpers sur le Model
if ($invoice->status->is(InvoiceStatus::published())) { … }
```

### Seeder obligatoire
Chaque Reference Model a un Seeder qui garantit les valeurs système de base :
```php
final class InvoiceStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'draft',     'label_fr' => 'Brouillon',  'label_en' => 'Draft',     'is_system' => true, 'sort_order' => 10],
            ['code' => 'published', 'label_fr' => 'Publiée',    'label_en' => 'Published', 'is_system' => true, 'sort_order' => 20],
            ['code' => 'cancelled', 'label_fr' => 'Annulée',    'label_en' => 'Cancelled', 'is_system' => true, 'sort_order' => 30],
            ['code' => 'paid',      'label_fr' => 'Payée',      'label_en' => 'Paid',      'is_system' => true, 'sort_order' => 40],
        ];

        foreach ($statuses as $data) {
            InvoiceStatus::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
```

## Validation
Dans les FormRequests :
```php
// ❌ Avant (avec Enum)
'status' => ['required', Rule::enum(InvoiceStatus::class)],

// ✅ Après (avec Reference Model)
'invoice_status_id' => [
    'required',
    Rule::exists('invoice_statuses', 'id')->where('is_active', true),
],
// ou par code pour les API publiques :
'status_code' => [
    'required',
    Rule::exists('invoice_statuses', 'code')->where('is_active', true),
],
```

## Cache
Les Reference Models sont lus **très souvent** et changent rarement. Cacher :
```php
// Dans ReferenceModel ou trait Cacheable
public static function byCode(string $code): static
{
    return Cache::remember(
        static::class . ":code:{$code}",
        now()->addHour(),
        fn () => static::query()->where('code', $code)->firstOrFail(),
    );
}

// Invalider au save
protected static function booted(): void
{
    parent::booted();
    static::saved(fn ($m) => Cache::forget(static::class . ":code:{$m->code}"));
}
```

## Exposition API
Une Resource standard pour toutes les Reference Models :
```php
final class ReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label(app()->getLocale()),
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
        ];
    }
}
```

## Administration (interface web)
Chaque Reference Model expose un CRUD admin standard via :
- `GET /api/v1/admin/{reference}` — liste (avec inactifs)
- `POST /api/v1/admin/{reference}` — création
- `PUT /api/v1/admin/{reference}/{id}` — modification
- `DELETE /api/v1/admin/{reference}/{id}` — suppression (refus si `is_system`)

Policy : réservé aux rôles admin via Spatie Permission.
