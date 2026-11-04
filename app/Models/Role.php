<?php

namespace App\Models;

use Backpack\PermissionManager\app\Models\Role as BackpackRole;

/**
 * Backpack's Role (Spatie's, plus CrudTrait), plus the activity log (config/permission.php points here).
 * Which permissions a role has is a many-to-many change, logged by RoleCrudController.
 */
class Role extends BackpackRole
{
    use Concerns\AuditsChanges;
}
