<?php

namespace App\ExchangeRateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\ExchangeRateBundle\Service\ExchangeRateService;

class ExchangeRatesController extends AbstractController
{
    private $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function fetchRates(Request $request): JsonResponse
    {
        $date = $request->query->get('date', (new \DateTime())->format('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['error' => 'Invalid date format. Expected YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($date < '2023-01-01') {
            return $this->json(['error' => 'Historical data is available only from 2023-01-01.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $selectedRates = $this->exchangeRateService->fetchExchangeRates($date);

            return $this->json([
                'data' => $selectedRates,
                'date' => $date,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
