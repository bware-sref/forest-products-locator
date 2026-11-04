<?php

use App\Enums\UserRoles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * activity_log.see opens the admin activity log (ActivityLogCrudController). Superadmins get it here so existing
 * databases have it without reseeding; fresh ones also get it from database/data/permissions*.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard');
        $permission = Permission::findOrCreate('activity_log.see', $guard);

        Role::where('name', UserRoles::SUPER->value)->where('guard_name', $guard)->first()
            ?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'activity_log.see')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
