# Conventions de tests (Pest)

## Exigences
- **Coverage > 80% en CI.** Pipeline bloque merge sous ce seuil.
- Chaque endpoint : 1 test Feature succès + 1 test erreur (validation/auth).
- Chaque Action et Service : test unitaire isolé.
- Chaque Policy : test `allow` + test `deny`.

## Langue
- Descriptions `it(...)` **en anglais** : `it('creates an invoice with the correct total')`.
- Noms de fichiers : PascalCase anglais : `CreateInvoiceTest.php`.
- Commentaires internes en français si pertinent.

## Organisation
```
tests/
├── Feature/
│   ├── Modules/Facturation/InvoiceControllerTest.php
│   └── Mcp/GetInvoiceToolTest.php
└── Unit/
    ├── Modules/Facturation/TaxCalculatorTest.php
    └── Support/MoneyTest.php
```

## Structure type
```php
<?php

declare(strict_types=1);

use App\Modules\Facturation\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists invoices for the authenticated user', function () {
    Invoice::factory()->count(3)->for($this->user)->create();

    $this->actingAs($this->user)
        ->getJson('/api/v1/invoices')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta' => ['total', 'per_page']]);
});

it('returns 403 when user does not own the invoice', function () {
    $other = Invoice::factory()->create();

    $this->actingAs($this->user)
        ->getJson("/api/v1/invoices/{$other->id}")
        ->assertForbidden();
});

it('rejects invalid status transitions', function (string $from, string $to) {
    $invoice = Invoice::factory()->create(['status' => $from]);

    expect(fn () => $invoice->transitionTo($to))
        ->toThrow(InvalidStatusTransitionException::class);
})->with([
    ['published', 'draft'],
    ['cancelled', 'published'],
]);
```

## Règles clés
- `uses(RefreshDatabase::class)` dans tous les tests Feature.
- Factories obligatoires — jamais d'`insert` direct.
- Un `it()` = un comportement.
- `assertJsonStructure` + `assertJsonPath` pour vérifier la réponse.
- Mocks parcimonieux : préférer vrais objets (Value Objects, petits Services).

## Configuration
- SQLite en mémoire pour tests Feature (vitesse).
- `.env.testing` pour variables spécifiques.
- `phpunit.xml` : `QUEUE_CONNECTION=sync`, `CACHE_DRIVER=array`, `MAIL_MAILER=array`.

## Pipeline CI
```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
./vendor/bin/pest --coverage --min=80
composer audit
```
