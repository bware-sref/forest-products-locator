<?php

namespace App\Jobs\Concerns;

use App\Enums\Environment;

trait SendsMailFromMultipleEnvironments
{
    //

    protected function applyEnvironmentPrefix(string $subject): string
    {
        if (Environment::Production->value !== config('app.env')) {
            $subject = config('mail.test.subject.prefix') . $subject;
        }

        return $subject;
    }
}
