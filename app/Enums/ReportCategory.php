<?php

namespace App\Enums;

enum ReportCategory: string
{
    case Network = 'network';
    case Device = 'device';
    case Electrical = 'electrical';
    case Projector = 'projector';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Network => 'Jaringan LAN',
            self::Device => 'Mouse, keyboard & PC',
            self::Electrical => 'Listrik & kabel',
            self::Projector => 'Proyektor',
            self::Other => 'Lainnya',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Network => 'wifi',
            self::Device => 'mouse',
            self::Electrical => 'bolt',
            self::Projector => 'projector',
            self::Other => 'dots',
        };
    }
}
