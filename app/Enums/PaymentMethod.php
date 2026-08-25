<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case DebitCard = 'debit_card';
    case CreditCard = 'credit_card';
    case EWallet = 'e_wallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::BankTransfer => 'Transfer bank',
            self::DebitCard => 'Kartu debit',
            self::CreditCard => 'Kartu kredit',
            self::EWallet => 'Dompet digital',
        };
    }
}
