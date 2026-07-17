<?php

namespace App\Enums;

enum ImportSourceType: string
{
    case Arcgis = 'arcgis';
    case Xlsx   = 'xlsx';
}
