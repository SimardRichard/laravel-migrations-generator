<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Enums;

enum OnAction: string
{
    case Cascade = 'cascade';
    case Restrict = 'restrict';
    case SetNull = 'set null';
    case NoAction = 'no action';
    case SetDefault = 'set default';

    public static function fromSql(string $raw): self
    {
        return self::tryFrom(strtolower(trim($raw))) ?? self::NoAction;
    }
}
