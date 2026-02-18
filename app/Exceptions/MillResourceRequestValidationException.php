<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;

class MillResourceRequestValidationException extends ValidationException
{
    // override the constructor to set the redirectTo member to null
    public function __construct($validator, $response = null, $errorBag = 'default')
    {
        parent::__construct($validator, $response, $errorBag);

        // make us go nowhere by default!
        $this->redirectTo = null;

        return;
    }
}
