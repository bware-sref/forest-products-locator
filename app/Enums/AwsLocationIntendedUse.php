<?php

namespace App\Enums;

enum AwsLocationIntendedUse: string
{
    //
    case SingleUse = 'SingleUse';
    case Storage = 'Storage';

    /**
     * Add a constant to alias SingleUse as Default
     */
    public const Default = self::SingleUse;
}
