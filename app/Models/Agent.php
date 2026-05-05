<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $title
 * @property string $email
 * @property string|null $phone
 * @property int|null $state_id
 * @property string|null $street_address
 * @property string|null $city
 * @property string|null $zip_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State|null $state
 * @method static \Database\Factories\AgentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereStreetAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereZipCode($value)
 * @mixin \Eloquent
 */
class Agent extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\AgentFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'title',
        'email',
        'phone',
        'state_id',
        'street_address',
        'city',
        'zip_code',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
