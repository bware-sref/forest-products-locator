<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
}
