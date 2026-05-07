<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers;

use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;

/**
 * Sérialise un IndexSchema en ligne PHP `$table->index(...)`.
 *
 * Renvoie null pour la PK (gérée par bigIncrements dans ColumnRenderer).
 */
final class IndexRenderer
{
    public function render(IndexSchema $idx): ?string
    {
        if ($idx->type === IndexType::Primary) {
            return null;
        }

        $columns = $idx->isComposite
            ? '['.implode(', ', array_map(fn (string $c): string => "'$c'", $idx->columns)).']'
            : "'".$idx->columns[0]."'";

        return sprintf("\$table->%s(%s, '%s');", $idx->type->value, $columns, $idx->name);
    }
}
