<?php

namespace App\ExchangeRateBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateService
{
    private $httpClient;
    private $nbpApiUrl;
    private $supportedCurrencies;
    private $currencyFormatter;

    public function __construct(HttpClientInterface $httpClient, string $nbpApiUrl, array $supportedCurrencies, CurrencyFormatter $currencyFormatter)
    {
        $this->httpClient = $httpClient;
        $this->nbpApiUrl = $nbpApiUrl;
        $this->supportedCurrencies = $supportedCurrencies;
        $this->currencyFormatter = $currencyFormatter;
    }

    public function fetchExchangeRates(string $date): array
    {
        $rates = [];
        foreach ($this->supportedCurrencies as $currency) {
            $rates[] = $this->fetchSingleCurrencyRate($currency, $date);
        }

        return $rates;
    }

    private function fetchSingleCurrencyRate(string $currency, string $date): array
    {
        $url = sprintf('%s/rates/A/%s/%s/?format=json', rtrim($this->nbpApiUrl, '/'), $currency, $date);
        $isToday = (new \DateTime())->format('Y-m-d') === $date;

        try {
            $response = $this->httpClient->request('GET', $url);

            if ($response->getStatusCode() === 404) {
                if ($isToday) {
                    return [
                        'currency' => $currency,
                        'name' => $this->currencyFormatter->formatCurrencyName($currency),
                        'error' => 'Dane dla dzisiejszej daty nie są jeszcze dostępne.',
                    ];
                }

                throw new \RuntimeException("No data available for currency {$currency} and date {$date}.");
            }

            $data = $response->toArray();
            $rate = isset($data['rates'][0]) ? $data['rates'][0] : [];

            return [
                'currency' => $currency,
                'name' => $this->currencyFormatter->formatCurrencyName($currency),
                'averageRate' => isset($rate['mid']) ? $rate['mid'] : null,
                'buyRate' => $this->calculateBuyRate($currency, isset($rate['mid']) ? $rate['mid'] : null),
                'sellRate' => $this->calculateSellRate($currency, isset($rate['mid']) ? $rate['mid'] : null),
            ];
        } catch (\Exception $e) {
            return [
                'currency' => $currency,
                'name' => $this->currencyFormatter->formatCurrencyName($currency),
                'error' => $e->getMessage(),
            ];
        }
    }

    private function calculateBuyRate(string $currency, $averageRate): ?float
    {
        if ($averageRate === null) {
            return null;
        }

        return in_array($currency, ['USD', 'EUR'], true) ? $averageRate - 0.05 : null;
    }

    private function calculateSellRate(string $currency, $averageRate): ?float
    {
        if ($averageRate === null) {
            return null;
        }

        return in_array($currency, ['USD', 'EUR'], true) ? $averageRate + 0.07 : $averageRate + 0.15;
    }
}
