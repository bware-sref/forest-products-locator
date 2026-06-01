<?php

namespace App\Enums;

enum RolesEnum: string
{
    // case NAMEINAPP = 'name-in-database';

    case SUPER = 'Superadmin';
    case ADMIN = 'Administrator';
    case EDITOR = 'Editor';

    public function label(): string
    {
        return match($this) {
            static::SUPER => 'Superadmins',
            static::ADMIN => 'Administrators',
            static::EDITOR => 'Editors',
        };
    }
}
