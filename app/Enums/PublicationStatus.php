<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Rejected = 'rejected';
}
