<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoodSpecies extends Model
{
    /** @use HasFactory<\Database\Factories\WoodSpeciesFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
