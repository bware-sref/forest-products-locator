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
    /**
     * Invalid is for items that encounter issues during post-import processing.
     * For example, if a mill is imported but has neither an address nor lat&lng with which to geocode it, it would be marked invalid.
     */
    case Invalid = 'invalid';
}
