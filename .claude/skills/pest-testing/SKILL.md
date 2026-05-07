---
name: pest-testing
description: Écrit et organise des tests Pest. Utiliser pour "écrire un test", "tester cette fonction", "ajouter un test feature/unit".
---

# Tests avec Pest

## Organisation
- `tests/Unit/` — isolation totale, pas de BD, pas de HTTP.
- `tests/Feature/` — avec BD (RefreshDatabase) et requêtes HTTP.
- `tests/Feature/Modules/{Name}/` — tests spécifiques à un module.

## Style
```php
<?php
declare(strict_types=1);

use App\Modules\Facturation\Models\Invoice;

it('crée une facture avec le bon total', function () {
    $invoice = Invoice::factory()->create(['amount' => 100]);

    expect($invoice->total)->toBe(115.0);  // avec TPS+TVQ
});

it('refuse une facture sans montant', function () {
    expect(fn () => Invoice::factory()->create(['amount' => null]))
        ->toThrow(ValidationException::class);
});
```

## Conventions
- Descriptions en français, commençant par le verbe (`it('crée…')`).
- Un `it()` = un comportement vérifié.
- Datasets pour tester plusieurs cas d'un même comportement.
- Factories pour toute donnée — jamais d'`insert` direct.

## Helpers HTTP
```php
$this->actingAs($user)
    ->postJson('/api/invoices', $payload)
    ->assertCreated()
    ->assertJsonPath('data.amount', 100);
```
