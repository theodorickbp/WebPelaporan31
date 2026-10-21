<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Siswa',
            self::Teacher => 'Guru',
            self::Admin => 'Admin / Toolman',
        };
    }
}
