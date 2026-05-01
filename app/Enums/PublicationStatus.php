<?php

namespace App\Enums;

/**
 * In retrospect, ApprovalStatus seems more appropriate.
 */
enum PublicationStatus: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Rejected = 'rejected';
}
