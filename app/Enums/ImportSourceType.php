<?php

namespace App\Enums;

enum ImportSourceType: string
{
    case Arcgis = 'arcgis';
    case Spreadsheet = 'spreadsheet';
    case User = 'user';

    /**
     * Make Xlsx an alias of Spreadsheet
     */
    public const Xlsx = self::Spreadsheet;
}
