<?php

namespace App\ExchangeRateBundle\Service;

class CurrencyFormatter
{
    private $currencyNames = [
        'USD' => 'Dolar amerykański',
        'CZK' => 'Korona czeska',
        'IDR' => 'Rupia indonezyjska',
        'BRL' => 'Real brazylijski',
        'EUR' => 'Euro',
    ];

    public function formatCurrencyName(string $currency): string
    {
        return $this->currencyNames[$currency] ?? $currency;
    }
}
