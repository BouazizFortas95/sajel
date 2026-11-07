<?php

namespace App\Enums;

enum OfficialExamDutyRole: string
{
    case RoomChief = 'room_chief';
    case RoomSupervisor = 'room_supervisor';
    case CorridorGuard = 'corridor_guard';
    case Secretariat = 'secretariat';
    case Reserve = 'reserve';

    public function label(): string
    {
        return match ($this) {
            self::RoomChief => __('رئيس القاعة المسؤول'),
            self::RoomSupervisor => __('أستاذ حارس بالقاعة'),
            self::CorridorGuard => __('حارس ممر وجناح'),
            self::Secretariat => __('عضو الأمانة العامة للمركز'),
            self::Reserve => __('أستاذ حارس احتياطي'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::RoomChief => 'primary',
            self::RoomSupervisor => 'info',
            self::CorridorGuard => 'warning',
            self::Secretariat => 'success',
            self::Reserve => 'gray',
        };
    }
}
