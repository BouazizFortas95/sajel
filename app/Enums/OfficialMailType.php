<?php

namespace App\Enums;

enum OfficialMailType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    public function label(): string
    {
        return match($this) {
            self::Incoming => __('وارد'),
            self::Outgoing => __('صادر'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Incoming => 'info',
            self::Outgoing => 'warning',
        };
    }

    public function prefix(): string
    {
        return match($this) {
            self::Incoming => 'ARR',
            self::Outgoing => 'DEP',
        };
    }
}

