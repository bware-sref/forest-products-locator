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
    use HasRoles {
        assignRole as protected spatieAssignRole;
    }

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
        // never limited to a state, even with the State Agent role as well
        if ($this->isExemptFromStateLimits()) {
            return false;
        }

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
     * Has the State Agent role but no state it can be limited to: state_id is empty or points at a state that
     * no longer exists (users.state_id has no foreign key). isStateAgent() is false for these users, so nothing
     * scopes them to a state; BlockIncompleteStateAgents keeps them out of the admin panel instead.
     * Users with a role in STATE_EXEMPT_ROLES are never limited to a state, so they're never incomplete.
     */
    public function isIncompleteStateAgent(): bool
    {
        if (! $this->hasRole(UserRoles::AGENT) || $this->isExemptFromStateLimits()) {
            return false;
        }

        return empty($this->state_id) || ! State::whereKey($this->state_id)->exists();
    }

    /**
     * One role per user. Spatie allows several; this refuses any assignment that would leave the user with more
     * than one, before anything is saved. To change a user's role, use syncRoles(): it removes the current roles
     * first, then calls this. (The admin user form saves through Eloquent's roles()->sync() instead, so its own
     * rule, ValidatesUserRoles, covers that path.)
     *
     * @param  string|int|array|\Spatie\Permission\Contracts\Role|\Illuminate\Support\Collection|\BackedEnum  ...$roles
     * @return $this
     *
     * @throws \DomainException
     */
    public function assignRole(...$roles)
    {
        $incoming = $this->collectRoles($roles);
        $current = $this->exists
            ? $this->roles()->pluck($this->roles()->getRelated()->getQualifiedKeyName())->all()
            : [];

        $resulting = collect([...$current, ...$incoming])->map(fn ($id) => (int) $id)->unique();

        if ($resulting->count() > 1) {
            $who = $this->exists ? "User #{$this->getKey()}" : 'A new user';

            throw new \DomainException("{$who} would have more than one role; a user can have only one. Use syncRoles() to change a user's role.");
        }

        return $this->spatieAssignRole(...$roles);
    }

    /**
     * Whether the user may enter the admin panel at all (CheckIfAdmin): at least one role or direct permission.
     * Removing a user's role (and any direct permissions) is how to suspend them without deleting the account.
     */
    public function hasAdminAccess(): bool
    {
        return $this->roles()->exists() || $this->permissions()->exists();
    }

    /**
     * True when this user is a Superadmin and no other user is. That role must always have someone in it:
     * only Superadmins can edit other users (UserCrudController::restrictToOwnRecordUnlessSuper()).
     */
    public function isLastSuperadmin(): bool
    {
        if (! $this->exists || ! $this->hasRole(UserRoles::SUPER)) {
            return false;
        }

        // role names directly: Spatie's User::role() throws if the role hasn't been created
        return ! static::query()
            ->whereKeyNot($this->getKey())
            ->whereHas('roles', fn ($query) => $query->where('name', UserRoles::SUPER->value))
            ->exists();
    }

    /**
     * Roles that are never limited to a state, even when a user also has the State Agent role
     * (Spatie lets a user hold several roles).
     */
    public const array STATE_EXEMPT_ROLES = [UserRoles::SUPER, UserRoles::ADMIN];

    public function isExemptFromStateLimits(): bool
    {
        return $this->hasAnyRole(self::STATE_EXEMPT_ROLES);
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
