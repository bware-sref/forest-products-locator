<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    /** @use HasFactory<\Database\Factories\StateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'latitude',
        'longitude',
        'polygon',
    ];

    // hasMany Counties
    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    // hasMany Mills
    public function mills(): HasMany
    {
        return $this->hasMany(Mill::class);
    }
}
