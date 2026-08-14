<?php

namespace App\Enums;

/**
 * An alternative to using string literals when evaluating app.env values.
 */
enum Environment: string
{
    /**
     * For some reason having these as literals all over the place bothers me.
     */
    case Development = 'development';
    case Local = 'local';
    case QA = 'qa';    
    case Production = 'production';
    case Staging = 'staging';
    case Testing = 'testing';

    /**
     * FFS, all backed cases must have a unique scalar value.
     * However, Enums can have constants, and those constants can reference cases, which effectively allows aliasing cases.
     * Aliasing Dev and Prod since they're commonly used.
     */
    public const Dev = self::Development;
    public const Prod = self::Production;
}
