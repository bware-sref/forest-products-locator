<?php

namespace App\Enums;

enum RolesEnum: string
{
    // case NAMEINAPP = 'name-in-database';

    case SUPER = 'super';
    case ADMIN = 'admin';
    case EDITOR = 'editor';

    public function label(): string
    {
        return match($this) {
            static::SUPER => 'Superadmins',
            static::ADMIN => 'Administrators',
            static::EDITOR => 'Editors',
        };
    }
}
