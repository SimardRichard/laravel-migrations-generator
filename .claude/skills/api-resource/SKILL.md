---
name: api-resource
description: Crée une ressource API REST complète. Utiliser pour "créer un endpoint", "nouvelle API", "CRUD".
---

# Créer une ressource API REST complète

## Checklist

1. **Module cible** : `app/Modules/{Module}/` (créer si absent, voir skill laravel-module)
2. **Migration** : UUID v7, FK avec contraintes, SoftDeletes, index sur colonnes filtrées (dans `Database/Migrations/`)
3. **Model** : hérite de `Model`, traits `HasUuidV7` + `HasFactory` + `SoftDeletes`, casts vers Reference Models / VO, PHPDoc `@property` complet
4. **Factory** : dans `app/Modules/{Module}/Database/Factories/{Name}Factory.php`
5. **Requests** : `Store{Name}Request` + `Update{Name}Request` (séparés, jamais partagés)
6. **Resource** : `{Name}Resource` (jamais exposer le Model direct)
7. **Schema OpenAPI** : `app/Modules/{Module}/OpenApi/{Name}Schema.php` correspondant au Resource — **obligatoire**
8. **Controller** : `apiResource` classique (index/show/store/update/destroy) avec attributs `#[OA\Get/Post/Put/Delete]` sur chaque méthode
9. **Routes** : `/api/v1/{resources-kebab}` dans `routes/api.php` du module — middleware `api` appliqué automatiquement par la base provider
10. **Tag OpenAPI** : ajouté dans `app/Modules/ApiDocs/OpenApi/OpenApiInfo.php` si nouveau scope (ex: `Invoices`, `Admin · Invoices`)
11. **Policy** : Spatie Permission + Policy si accès restreint
12. **Reference Models** utilisés pour valeurs énumérées (voir `rules/reference-models.md`)
13. **Module Config** si paramètres métier (voir `rules/module-config.md`)
14. **Tests Pest Feature** (anglais) : 1 cas nominal + 1 cas erreur par méthode + un test pour chaque code de réponse documenté
15. **Régénérer la doc** : `php artisan l5-swagger:generate`

## Format de réponse (obligatoire)

```json
{
  "data": { ... },
  "meta": { "current_page": 1, "total": 98 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

## Squelette d'une méthode controller annotée

```php
#[OA\Post(
    path: '/api/v1/invoices',
    summary: 'Create an invoice',
    tags: ['Invoices'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['client_id', 'amount'],
            properties: [
                new OA\Property(property: 'client_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'amount', type: 'string', example: '120.75'),
                new OA\Property(property: 'currency', type: 'string', example: 'CAD'),
            ],
        ),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Invoice'),
        ])),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ],
)]
public function store(StoreInvoiceRequest $request): InvoiceResource
{
    // ...
}
```

Voir `.claude/rules/api-design.md` (format) et `.claude/rules/api-documentation.md` (Swagger) pour le détail.
