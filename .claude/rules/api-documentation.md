# Documentation API — Swagger / OpenAPI obligatoire

Le projet expose un Swagger UI à `/api/docs` alimenté par
`darkaonline/l5-swagger`. Les annotations sont **obligatoires** sur tout
endpoint exposé par un module.

## Règles strictes

- **Tout controller** d'un endpoint REST a un attribut PHP 8 `#[OA\Get]`,
  `#[OA\Post]`, `#[OA\Put]`, `#[OA\Patch]` ou `#[OA\Delete]` sur la méthode.
- **Tout Resource** ou DTO de réponse a son `Schema` dans
  `app/Modules/{Name}/OpenApi/{Name}Schema.php`.
- **Tout endpoint protégé** déclare `security: [['sanctum' => []]]`.
- **Toute réponse 422** référence le schema commun `#/components/schemas/ValidationError`.
- **Toute liste paginée** référence `PaginationMeta` et `PaginationLinks`.
- **Tags** : utiliser ceux déclarés dans `App\Modules\ApiDocs\OpenApi\OpenApiInfo`
  ou ajouter le nouveau tag dans ce fichier (description en français).
- Régénérer le spec : `php artisan l5-swagger:generate` avant chaque PR.

## Pattern minimal pour un nouveau module

### 1. Créer le dossier `OpenApi/` dans le module

```
app/Modules/{Name}/
└── OpenApi/
    ├── {Name}Schema.php        # 1 schema par Resource principal
    └── {SubResource}Schema.php # 1 par autre Resource si pertinent
```

### 2. Schema type

```php
<?php

declare(strict_types=1);

namespace App\Modules\Facturation\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Invoice',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'number', type: 'string', example: 'INV-2026-0001'),
        new OA\Property(property: 'amount', type: 'string', example: '120.75'),
        new OA\Property(property: 'currency', type: 'string', example: 'CAD'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'paid', 'cancelled']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class InvoiceSchema {}
```

### 3. Annoter chaque méthode du Controller

```php
#[OA\Get(
    path: '/api/v1/invoices/{invoice}',
    summary: 'Retrieve an invoice',
    tags: ['Invoices'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Invoice',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Invoice'),
            ]),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
public function show(Invoice $invoice): InvoiceResource
{
    return InvoiceResource::make($invoice);
}
```

### 4. Si nouveau tag → l'ajouter dans `OpenApiInfo`

```php
// app/Modules/ApiDocs/OpenApi/OpenApiInfo.php
#[OA\Tag(name: 'Invoices', description: 'Facturation client')]
#[OA\Tag(name: 'Admin · Invoices', description: 'CRUD administration des factures')]
```

## Anti-patterns interdits

- ❌ Controller sans aucun attribut `#[OA\...]` → la doc sera incomplète
- ❌ `OA\JsonContent` inline pour des structures réutilisables → préférer un schema centralisé
- ❌ Hardcoder le path en double avec la route → si la route change le path doit suivre
- ❌ Oublier `security` sur les endpoints sous `auth:sanctum`
- ❌ Oublier la réponse 422 sur les endpoints qui valident un FormRequest

## Schemas partagés disponibles

| Schema | Module | Usage |
| --- | --- | --- |
| `ValidationError` | ApiDocs | Réponse 422 standardisée Laravel |
| `PaginationLinks` | ApiDocs | Bloc `links` des listes paginées |
| `PaginationMeta` | ApiDocs | Bloc `meta` des listes paginées |
| `User` | User | Compte utilisateur |
| `Language` | Language | Langue (Reference Model) |
| `Module` | Module | Entrée du registre des modules |
