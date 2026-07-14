<?php

namespace App\Models;

use App\Enums\UserRoles;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens;
    use HasFactory, Notifiable, HasApiTokens;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'state_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Is the current user an administrator?
     */
    public function isAdmin(): bool
    {
        /**
         * Use hasRole() instead.
         * hasRole() accepts BackedEnum values.
         */
        return $this->hasRole(UserRoles::ADMIN);
    }

    /**
     * Is the current user a superadmin?
     */
    public function isSuper(): bool
    {
        return $this->hasRole(UserRoles::SUPER);
    }

    /**
     * Still need to flesh out state agent stuff before we know how to evaluate this.
     * Adding the method now anyway so it can be used in Policies.
     */
    public function isStateAgent(): bool
    {
        return $this->hasRole(UserRoles::AGENT);
    }

    /**
     * The purpose of Editor is still unclear.
     */
    public function isEditor(): bool
    {
        return $this->hasRole(UserRoles::EDITOR);
    }

    /**
     * This is one of the questions.
     * Do we even need a related model or should we just add a state_id field to User and use the role?
     */
    public function isAgentFor(Model $model, string $key = 'state_id'): bool
    {
        return !empty($model->$key) && ($this->state_id === $model->$key);
    }
}
