<?php

namespace Integration\ExchangeRate;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExchangeRatesControllerTest extends WebTestCase
{
    public function testFetchExchangeRatesSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/exchange-rates?date=2023-01-01');

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('date', $responseData);

        $this->assertIsArray($responseData['data']);
        foreach ($responseData['data'] as $rate) {
            $this->assertArrayHasKey('currency', $rate);
            $this->assertArrayHasKey('name', $rate);

            if (isset($rate['error'])) {
                $this->assertStringContainsString('No data available for currency', $rate['error']);
            } else {
                $this->assertArrayHasKey('averageRate', $rate);
                $this->assertArrayHasKey('buyRate', $rate);
                $this->assertArrayHasKey('sellRate', $rate);
            }
        }
    }
    public function testFetchExchangeRatesInvalidDate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/exchange-rates?date=invalid-date');

        $this->assertResponseStatusCodeSame(400);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid date format. Expected YYYY-MM-DD.', $responseData['error']);
    }

    public function testFetchExchangeRatesHistoricalLimit(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/exchange-rates?date=2022-12-31');

        $this->assertResponseStatusCodeSame(400);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Historical data is available only from 2023-01-01.', $responseData['error']);
    }

    public function testFetchExchangeRatesNoDataForToday(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/exchange-rates?date=' . date('Y-m-d'));

        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        foreach ($responseData['data'] as $rate) {
            if (isset($rate['error'])) {
                $this->assertStringContainsString('Dane dla dzisiejszej daty nie są jeszcze dostępne.', $rate['error']);
            } else {
                $this->assertArrayHasKey('averageRate', $rate);
                $this->assertArrayHasKey('buyRate', $rate);
                $this->assertArrayHasKey('sellRate', $rate);
            }
        }
    }
}
