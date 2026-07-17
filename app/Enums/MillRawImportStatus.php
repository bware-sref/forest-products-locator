<?php

namespace App\Enums;

enum MillRawImportStatus: string
{
    case Pending   = 'pending';
    case Processed = 'processed';
    case Failed    = 'failed';
}
