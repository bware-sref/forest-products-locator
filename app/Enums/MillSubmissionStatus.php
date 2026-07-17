<?php

namespace App\Enums;

enum MillSubmissionStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Failed   = 'failed';
}
