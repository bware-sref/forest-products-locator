<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Published = 'published';
    case Pending = 'pending';
    case Rejected = 'rejected';
}
