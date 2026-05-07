# Conventions API REST

## Versioning
- **Préfixe URL `/api/v1`** obligatoire.
- `/api/v2` uniquement pour breaking change ; coexistence avec v1 pendant au moins 1 cycle avant deprecation.

## Format de réponse — enveloppe `{data, meta}`

### Ressource unique
```json
{ "data": { "id": "…", "number": "INV-2026-0001", "amount": "100.00" } }
```

### Liste paginée
```json
{
    "data": [ … ],
    "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 98 },
    "links": { "first": "…", "last": "…", "prev": null, "next": "…" }
}
```

### Erreur
```json
{
    "message": "The given data was invalid.",
    "errors": { "amount": ["The amount field is required."] }
}
```

## Codes HTTP
| Code | Utilisation                              |
| ---- | ---------------------------------------- |
| 200  | GET succès                               |
| 201  | POST crée ressource                      |
| 204  | DELETE succès                            |
| 401  | Non authentifié                          |
| 403  | Authentifié mais non autorisé (Policy)   |
| 404  | Ressource introuvable                    |
| 409  | Conflit d'état                           |
| 422  | Validation FormRequest                   |
| 429  | Rate limit dépassé                       |

## Controllers — apiResource classique
```php
Route::prefix('api/v1')->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});
```
5 méthodes REST : `index`, `show`, `store`, `update`, `destroy`.
Actions non-CRUD → méthode dédiée dans le même Controller ou Controller invokable séparé si substantielle.

## Validation — FormRequest par action
- **Une Request par action** : `StoreInvoiceRequest`, `UpdateInvoiceRequest` (pas de partage).
- `Update` utilise `sometimes` + règles conditionnelles sur transitions.
- Messages dans `lang/{fr,en}/validation.php` — **jamais hardcodés**.
- **Reference Models** validés via `Rule::exists('{table}', 'id'|'code')->where('is_active', true)`.
- `Rule::enum(...)` réservé aux enums techniques résiduels (voir `reference-models.md`).
- Rule Objects custom dans `app/Modules/{M}/Rules/` pour règles métier réutilisables.

## Transformation — Resources systématiques
- **Jamais** `return $invoice;` dans un Controller.
- Toujours `Resource` / `Resource::collection()`.
- Relations chargées : `whenLoaded()` pour éviter N+1 silencieux.

```php
final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->value,
            'amount' => $this->amount->formatted(),
            'client' => ClientResource::make($this->whenLoaded('client')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

## Authentification — configurable
- **Laravel Sanctum** (SPA/mobile/tokens personnels) — par défaut.
- **JWT** stateless pour intégrations B2B stateless.
- Configuration dans `config/auth.php` + middleware adapté.

## Autorisation — Spatie Permission + Policies
- Package `spatie/laravel-permission` pour rôles/permissions en BD.
- Trait `HasRoles` sur `User`.
- Policies par Model, `authorize()` dans FormRequest ou Controller.
- `$user->can('invoices.publish')` + `$user->hasRole('admin')`.

## Rate limiting
- Par défaut `throttle:api` (60 req/min par token).
- Endpoints sensibles (login, reset password) : `throttle:5,1` + par IP.
