<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers;

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\RawDefault;

/**
 * Sérialise un ColumnSchema en ligne PHP `$table->...()`.
 *
 * Cas particulier `bigIncrements()` détecté quand la colonne est à la fois
 * BigInteger + unsigned + auto-increment (convention Laravel pour la PK).
 * Type Raw → addColumn('text', ...) avec marqueur // unmapped.
 */
final class ColumnRenderer
{
    public function render(ColumnSchema $col): string
    {
        if ($this->isAutoIncrementBigInteger($col)) {
            return sprintf("\$table->bigIncrements('%s');", $col->name);
        }

        if ($col->type === ColumnType::Raw) {
            return sprintf(
                "\$table->addColumn('text', '%s'); // unmapped — review and replace with the correct method",
                $col->name,
            );
        }

        return $this->renderBase($col).$this->renderModifiers($col).';';
    }

    private function isAutoIncrementBigInteger(ColumnSchema $col): bool
    {
        return $col->autoIncrement && $col->unsigned && $col->type === ColumnType::BigInteger;
    }

    private function renderBase(ColumnSchema $col): string
    {
        $method = $col->type->value;
        $args = match ($col->type) {
            ColumnType::String, ColumnType::Char => $col->length !== null
                ? sprintf("'%s', %d", $col->name, $col->length)
                : sprintf("'%s'", $col->name),
            ColumnType::Decimal => sprintf("'%s', %d, %d", $col->name, $col->precision ?? 8, $col->scale ?? 2),
            default => sprintf("'%s'", $col->name),
        };

        return sprintf('$table->%s(%s)', $method, $args);
    }

    private function renderModifiers(ColumnSchema $col): string
    {
        $out = '';

        if ($col->nullable) {
            $out .= '->nullable()';
        }

        if ($col->default !== null) {
            $out .= '->default('.$this->renderDefault($col->default).')';
        }

        if ($col->comment !== null) {
            $out .= sprintf("->comment('%s')", addslashes($col->comment));
        }

        return $out;
    }

    private function renderDefault(mixed $default): string
    {
        return match (true) {
            $default instanceof RawDefault => sprintf("DB::raw('%s')", $default->expression()),
            is_bool($default) => $default ? 'true' : 'false',
            is_int($default), is_float($default) => (string) $default,
            default => "'".addslashes((string) $default)."'",
        };
    }
}
