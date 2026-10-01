<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case JazzCash = 'jazzcash';
    case EasyPaisa = 'easypaisa';
    case Card = 'card';
    case Manual = 'manual';
}
