<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers;

use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;

/**
 * Sérialise un ForeignKeySchema en ligne fluent
 * `$table->foreign(...)->references(...)->on(...)->onUpdate(...)->onDelete(...)`.
 */
final class ForeignKeyRenderer
{
    public function render(ForeignKeySchema $fk): string
    {
        return sprintf(
            "\$table->foreign(%s, '%s')->references(%s)->on('%s')->onUpdate('%s')->onDelete('%s');",
            $this->columnsExpr($fk->columns),
            $fk->name,
            $this->columnsExpr($fk->referencedColumns),
            $fk->referencedTable,
            $fk->onUpdate->value,
            $fk->onDelete->value,
        );
    }

    /** @param string[] $columns */
    private function columnsExpr(array $columns): string
    {
        if (count($columns) === 1) {
            return "'".$columns[0]."'";
        }

        return '['.implode(', ', array_map(fn (string $c): string => "'$c'", $columns)).']';
    }
}
