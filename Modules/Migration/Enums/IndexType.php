<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Enums;

enum IndexType: string
{
    case Primary = 'primary';
    case Unique = 'unique';
    case Index = 'index';
    case FullText = 'fullText';
    case Spatial = 'spatialIndex';
}
