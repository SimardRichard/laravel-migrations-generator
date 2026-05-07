<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Services;

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;

/**
 * Mapping des types natifs SQL vers le ColumnType enum interne.
 *
 * Service partagé entre le sous-module Generator (lecture du schéma) et
 * Extract (analyse des cibles). Vit donc au niveau 2.
 */
final class ColumnTypeResolver
{
    /** @var array<string, ColumnType> */
    private const SQLITE_MAP = [
        'INTEGER' => ColumnType::Integer,
        'INT' => ColumnType::Integer,
        'BIGINT' => ColumnType::BigInteger,
        'SMALLINT' => ColumnType::SmallInteger,
        'TINYINT' => ColumnType::TinyInteger,
        'MEDIUMINT' => ColumnType::MediumInteger,
        'VARCHAR' => ColumnType::String,
        'CHAR' => ColumnType::Char,
        'TEXT' => ColumnType::Text,
        'CLOB' => ColumnType::Text,
        'BLOB' => ColumnType::Binary,
        'REAL' => ColumnType::Double,
        'DOUBLE' => ColumnType::Double,
        'FLOAT' => ColumnType::Float,
        'NUMERIC' => ColumnType::Decimal,
        'DECIMAL' => ColumnType::Decimal,
        'BOOLEAN' => ColumnType::Boolean,
        'DATE' => ColumnType::Date,
        'DATETIME' => ColumnType::DateTime,
        'TIME' => ColumnType::Time,
        'TIMESTAMP' => ColumnType::Timestamp,
        'YEAR' => ColumnType::Year,
        'JSON' => ColumnType::Json,
        'UUID' => ColumnType::Uuid,
    ];

    public function forSqlite(string $native): ColumnType
    {
        $base = strtoupper(trim(preg_replace('/\(.*$/', '', $native) ?? $native));

        return self::SQLITE_MAP[$base] ?? ColumnType::Raw;
    }
}
