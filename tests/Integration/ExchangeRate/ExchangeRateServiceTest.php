<?php

namespace Integration\ExchangeRate;

use PHPUnit\Framework\TestCase;
use App\ExchangeRateBundle\Service\ExchangeRateService;
use App\ExchangeRateBundle\Service\CurrencyFormatter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ExchangeRateServiceTest extends TestCase
{
    private $httpClientMock;
    private $currencyFormatterMock;
    private $service;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->currencyFormatterMock = $this->createMock(CurrencyFormatter::class);

        $this->currencyFormatterMock->method('formatCurrencyName')
            ->willReturnCallback(function ($currency) {
                $map = [
                    'USD' => 'Dolar amerykański',
                    'EUR' => 'Euro',
                    'CZK' => 'Korona czeska',
                ];
                return $map[$currency] ?? $currency;
            });

        $this->service = new ExchangeRateService(
            $this->httpClientMock,
            'https://api.nbp.pl/api',
            ['USD', 'EUR', 'CZK'],
            $this->currencyFormatterMock
        );
    }

    public function testFetchExchangeRatesSuccess(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'rates' => [
                [
                    'mid' => 4.5,
                ],
            ],
            'currency' => 'Dolar amerykański',
        ]);

        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->service->fetchExchangeRates('2023-01-01');

        $this->assertCount(3, $result);
        $this->assertEquals('Dolar amerykański', $result[0]['name']);
        $this->assertEquals(4.5, $result[0]['averageRate']);
    }

    public function testFetchExchangeRatesWithError(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->method('getStatusCode')->willReturn(404);

        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->service->fetchExchangeRates('2023-01-01');

        $this->assertCount(3, $result);
        foreach ($result as $rate) {
            $this->assertArrayHasKey('error', $rate);
            $this->assertStringContainsString('No data available for currency', $rate['error']);
        }
    }
}
