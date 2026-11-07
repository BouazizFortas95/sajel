<?php

namespace App\Enums;

enum UtilityPaymentStatus: string
{
    case ReceivedPendingAudit = 'received_pending_audit';
    case AuditedApproved = 'audited_approved';
    case MandatedPaid = 'mandated_paid';
    case ContestedOverbilled = 'contested_overbilled';

    public function label(): string
    {
        return match ($this) {
            self::ReceivedPendingAudit => __('مستلمة قيد المراجعة والمطابقة (Reçue en cours de vérification)'),
            self::AuditedApproved => __('تمت المطابقة والاعتماد للصرف (Vérifiée et approuvée)'),
            self::MandatedPaid => __('تم تسديد الفاتورة بحوالة مالية (Mandatée et payée)'),
            self::ContestedOverbilled => __('فاتورة محل اعتراض وطعن لاستهلاك غير مبرر (Contestée / Surfacturation)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ReceivedPendingAudit => 'info',
            self::AuditedApproved => 'primary',
            self::MandatedPaid => 'success',
            self::ContestedOverbilled => 'danger',
        };
    }
}

