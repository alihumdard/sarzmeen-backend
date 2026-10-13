<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentType: string
{
    case FullPayment = 'full_payment';
    case Installment = 'installment';
    case BookingOpen = 'booking_open';
    case CashInstallment = 'cash_installment';

    public function label(): string
    {
        return match ($this) {
            self::FullPayment => 'Full Payment',
            self::Installment => 'Installment',
            self::BookingOpen => 'Booking Open',
            self::CashInstallment => 'Cash + Installment',
        };
    }
}
