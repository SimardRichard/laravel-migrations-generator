---
name: reference-model
description: Crée un Reference Model (table de référence admin-éditable). Utiliser pour "nouveau reference model", "créer un statut/type/catégorie en BD".
---

# Créer un Reference Model

Pour toute valeur énumérée métier (statut, type, catégorie) — PAS d'Enum PHP.

## Procédure
1. **Migration** dans `app/Modules/{M}/database/migrations/` :
   ```php
   Schema::create('{snake_plural}', function (Blueprint $table) {
       $table->uuid('id')->primary();
       $table->string('code')->unique();
       $table->string('label_fr');
       $table->string('label_en');
       $table->text('description')->nullable();
       $table->string('color', 7)->nullable();
       $table->string('icon')->nullable();
       $table->unsignedInteger('sort_order')->default(0);
       $table->boolean('is_active')->default(true);
       $table->boolean('is_system')->default(false);
       $table->timestamps();
       $table->softDeletes();
       $table->index(['is_active', 'sort_order']);
   });
   ```

2. **Model** dans `app/Modules/{M}/Models/` :
   ```php
   use App\Support\Models\ReferenceModel;

   final class InvoiceStatus extends ReferenceModel
   {
       public const CODE_DRAFT = 'draft';
       public const CODE_PUBLISHED = 'published';

       protected $table = 'invoice_statuses';
   }
   ```

3. **Seeder** dans `app/Modules/{M}/database/seeders/` — crée les valeurs système avec `is_system=true`.

4. **Filament Resource** dans `app/Modules/{M}/Filament/Resources/` pour gestion admin.

5. **Test** dans `tests/Feature/Modules/{M}/` vérifiant : seeding, byCode, label multilingue, protection is_system.

6. **Si exposé en API admin** (CRUD `/api/v1/admin/{plural}`) :
   - Schema OpenAPI dans `app/Modules/{M}/OpenApi/{Name}Schema.php`
   - Attributs `#[OA\Get/Post/Put/Delete]` sur le controller
   - Tag déclaré dans `app/Modules/ApiDocs/OpenApi/OpenApiInfo.php`

Voir `.claude/rules/reference-models.md` et `.claude/rules/api-documentation.md` pour les détails.
