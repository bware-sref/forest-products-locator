<?php

namespace App\Models;

use Backpack\PermissionManager\app\Models\Permission as BackpackPermission;

/**
 * Backpack's Permission (Spatie's, plus CrudTrait), plus the activity log (config/permission.php points here).
 */
class Permission extends BackpackPermission
{
    use Concerns\AuditsChanges;
}
