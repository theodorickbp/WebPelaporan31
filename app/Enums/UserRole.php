<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Admin = 'admin';
    case Toolman = 'toolman';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Siswa',
            self::Admin => 'Admin',
            self::Toolman => 'Toolman',
        };
    }
}
