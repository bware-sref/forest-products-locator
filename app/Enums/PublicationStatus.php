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
     * Invalid is for Mills that encounter issues that are not errors (but which need to be differentiated somehow) 
     * during post-import processing.
     * For example, if after geocoding, a mill still doesn't have an addresss (or, more importantly, lat&lng) it would be 
     * considered invalid.
     */
    case Invalid = 'invalid';

    /**
     * Error is used for Mills that encounter errors during post-import processing
     * For example, if a mill is imported but has neither an address nor lat&lng with which to geocode it, it would be marked invalid.
     * If an error occurred preventing us from completing the import for an item, then it would be marked error.
     */
    case Error = 'error';
}
