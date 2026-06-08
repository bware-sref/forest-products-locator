<?php

namespace App\Enums;

enum UserRoles: string
{
    // case NAMEINAPP = 'name-in-database';

    case SUPER = 'Superadmin';
    case ADMIN = 'Administrator';
    case EDITOR = 'Editor';
    case AGENT = 'State Agent';

    public function label(): string
    {
        return match($this) {
            static::SUPER => 'Superadmins',
            static::ADMIN => 'Administrators',
            static::EDITOR => 'Editors',
            static::AGENT => 'State Agents',
        };
    }
}
