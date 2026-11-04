<?php

use App\Enums\UserRoles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * statistics.compare_states opens Statistics > Compare States, which puts states' data completeness, data age and
 * review times side by side. That can read as a scorecard of each state's staff, so it starts as Superadmin only.
 * Fresh databases also get it from database/data/permissions*.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard');
        $permission = Permission::findOrCreate('statistics.compare_states', $guard);

        Role::where('name', UserRoles::SUPER->value)->where('guard_name', $guard)->first()
            ?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'statistics.compare_states')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
