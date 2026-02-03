<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MillType extends Model
{
    /** @use HasFactory<\Database\Factories\MillTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * MillType belongs to many Mills
     */
    public function mills(): BelongsToMany
    {
        return $this->belongsToMany(Mill::class);
    }

}
