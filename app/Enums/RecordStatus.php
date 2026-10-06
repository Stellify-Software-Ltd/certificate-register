<?php

namespace App\Enums;

enum RecordStatus: string
{
    case Valid = 'valid';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case NoExpiry = 'no_expiry';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::ExpiringSoon => 'Expiring soon',
            self::Expired => 'Expired',
            self::NoExpiry => 'No expiry',
        };
    }

    /**
     * CSS class suffix for the status chip in the views.
     */
    public function chip(): string
    {
        return match ($this) {
            self::Valid => 'valid',
            self::ExpiringSoon => 'soon',
            self::Expired => 'expired',
            self::NoExpiry => 'none',
        };
    }
}
