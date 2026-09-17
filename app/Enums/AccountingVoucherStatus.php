<?php

namespace App\Enums;

enum AccountingVoucherStatus: string
{
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
