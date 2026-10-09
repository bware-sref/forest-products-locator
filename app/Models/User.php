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
use Illuminate\Support\Facades\Log;
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
     * I was tempted to make this method throw an exception if the user has the role but
     * doesn't have a state_id.
     * Instead, I think I'll just have it check for the role and state_id.
     * Tempting to log a warning if the user has a state but not the role, or vice versa.
     */
    public function isStateAgent(): bool
    {
        $hasRole = $this->hasRole(UserRoles::AGENT);
        $hasState = ! empty($this->state_id);
        /**
         * Log warnings for weird situations that should not exist.
         */
        if ($hasRole && ! $hasState) {
            Log::warning("\n".self::class."::isStateAgent():\nuser #{$this->id} has the StateAgent role, but does not have a state_id.");
            return false;
        } else if (! $hasRole && $hasState) {
            Log::warning("\n".self::class."::isStateAgent():\nuser #{$this->id} has a state_id but does not have the StateAgent role.");
            return false;
        }
        return $hasRole && $hasState;
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
