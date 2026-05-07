<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Enums;

enum ColumnType: string
{
    case Char = 'char';
    case String = 'string';
    case Text = 'text';
    case MediumText = 'mediumText';
    case LongText = 'longText';

    case TinyInteger = 'tinyInteger';
    case SmallInteger = 'smallInteger';
    case MediumInteger = 'mediumInteger';
    case Integer = 'integer';
    case BigInteger = 'bigInteger';
    case UnsignedTinyInteger = 'unsignedTinyInteger';
    case UnsignedSmallInteger = 'unsignedSmallInteger';
    case UnsignedMediumInteger = 'unsignedMediumInteger';
    case UnsignedInteger = 'unsignedInteger';
    case UnsignedBigInteger = 'unsignedBigInteger';

    case Float = 'float';
    case Double = 'double';
    case Decimal = 'decimal';

    case Boolean = 'boolean';

    case Date = 'date';
    case DateTime = 'dateTime';
    case DateTimeTz = 'dateTimeTz';
    case Time = 'time';
    case TimeTz = 'timeTz';
    case Timestamp = 'timestamp';
    case TimestampTz = 'timestampTz';
    case Year = 'year';

    case Binary = 'binary';
    case Json = 'json';
    case Jsonb = 'jsonb';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
    case IpAddress = 'ipAddress';
    case MacAddress = 'macAddress';
    case Geometry = 'geometry';
    case Point = 'point';
    case Enum = 'enum';
    case Set = 'set';
    case Raw = 'raw';
}
