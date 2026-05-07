<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\Interfaces\StubRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

/**
 * Action 3 : encapsule chaque MigrationFile dans son stub final.
 *
 * - `*_create_tables.php` → stub `migration.create`
 * - `*_add_foreign_keys.php` → stub `migration.foreign-keys`
 *
 * Le contenu interne (produit par BuildMigrationPlanAction) contient
 * `up + BODY_SEPARATOR + down`, qui est splitté ici pour alimenter les
 * placeholders `{{up_body}}` / `{{down_body}}` du stub.
 */
final readonly class RenderMigrationAction
{
    public function __construct(private StubRenderer $stubs) {}

    public function execute(MigrationPlan $plan): MigrationPlan
    {
        $rendered = [];
        foreach ($plan->files as $file) {
            $stub = str_contains($file->filename, 'add_foreign_keys') ? 'migration.foreign-keys' : 'migration.create';
            [$up, $down] = $this->splitBody($file->contents);

            $rendered[] = new MigrationFile(
                filename: $file->filename,
                contents: $this->stubs->render($stub, ['up_body' => $up, 'down_body' => $down]),
            );
        }

        return new MigrationPlan($rendered);
    }

    /** @return array{0:string, 1:string} */
    private function splitBody(string $combined): array
    {
        $parts = explode(BuildMigrationPlanAction::BODY_SEPARATOR, $combined, 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
