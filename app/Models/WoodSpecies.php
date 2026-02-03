<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WoodSpecies extends Model
{
    /** @use HasFactory<\Database\Factories\WoodSpeciesFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * WoodSpecies belongs to many Mills
     */
    public function mills(): BelongsToMany
    {
        return $this->belongsToMany(Mill::class);
    }

}
