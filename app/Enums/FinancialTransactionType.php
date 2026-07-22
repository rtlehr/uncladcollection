<?php

namespace App\Enums;

enum FinancialTransactionType: string
{
    case Payment = 'payment';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Fee = 'fee';
}
