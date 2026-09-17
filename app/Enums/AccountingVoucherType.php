<?php

namespace App\Enums;

enum AccountingVoucherType: string
{
    case Payment = 'payment';
    case Receipt = 'receipt';
    case Contra = 'contra';
    case Journal = 'journal';
    case Sales = 'sales';
    case Purchase = 'purchase';
    case DebitNote = 'debit_note';
    case CreditNote = 'credit_note';
    case Opening = 'opening';

    public function prefix(): string
    {
        return match ($this) {
            self::Payment => 'PAY',
            self::Receipt => 'REC',
            self::Contra => 'CNT',
            self::Journal => 'JV',
            self::Sales => 'SAL',
            self::Purchase => 'PUR',
            self::DebitNote => 'DN',
            self::CreditNote => 'CN',
            self::Opening => 'OB',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment',
            self::Receipt => 'Receipt',
            self::Contra => 'Contra',
            self::Journal => 'Journal',
            self::Sales => 'Sales',
            self::Purchase => 'Purchase',
            self::DebitNote => 'Debit Note',
            self::CreditNote => 'Credit Note',
            self::Opening => 'Opening Balance',
        };
    }
}
