---
name: module-config
description: Crée un Module Config Model (configuration métier en BD, gérable via admin). Utiliser pour "nouveau module config", "configuration admin-éditable".
---

# Créer un Module Config

Pour toute configuration métier d'un module modifiable via interface admin — PAS `config/*.php`.

## Procédure
1. **Migration** dans `app/Modules/{M}/database/migrations/create_{snake}_configs_table.php` :
   ```php
   Schema::create('{snake}_configs', function (Blueprint $table) {
       $table->uuid('id')->primary();
       $table->string('key')->unique();
       $table->json('value');
       $table->string('type');
       $table->string('group')->default('general');
       $table->string('label_fr');
       $table->string('label_en');
       $table->text('description')->nullable();
       $table->json('validation_rules')->nullable();
       $table->json('options')->nullable();
       $table->boolean('is_editable')->default(true);
       $table->boolean('is_system')->default(false);
       $table->unsignedInteger('sort_order')->default(0);
       $table->timestamps();
       $table->index(['group', 'sort_order']);
   });
   ```

2. **Model** dans `app/Modules/{M}/Models/` :
   ```php
   use App\Support\Models\ModuleConfig;

   final class FacturationConfig extends ModuleConfig
   {
       public const KEY_DEFAULT_TAX_RATE = 'default_tax_rate';
       public const KEY_INVOICE_PREFIX = 'invoice_number_prefix';

       protected $table = 'facturation_configs';
   }
   ```

3. **Seeder** crée les entrées système (`is_system=true`) avec validation_rules JSON.

4. **Usage dans le code** :
   ```php
   $rate = FacturationConfig::get(FacturationConfig::KEY_DEFAULT_TAX_RATE, 0.15);
   FacturationConfig::set('invoice_number_prefix', 'INV-2026-');
   ```

5. **Filament Resource** CRUD pour admin : affiche labels, description, validation_rules; respecte is_editable/is_system.

6. **Si exposé en API admin** (`/api/v1/admin/modules/{module}/configs`) :
   - Schema OpenAPI dans `app/Modules/{M}/OpenApi/{Module}ConfigSchema.php`
   - Attributs `#[OA\Get/Put]` sur le controller
   - Tag `Admin · {Module} Config` ajouté dans `app/Modules/ApiDocs/OpenApi/OpenApiInfo.php`

7. **Test** : seeding, get/set/cache invalidation, protection is_system, validation_rules appliquées à l'écriture.

Voir `.claude/rules/module-config.md` et `.claude/rules/api-documentation.md` pour les détails.
