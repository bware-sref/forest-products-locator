<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MillType extends Model
{
    /** @use HasFactory<\Database\Factories\MillTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
