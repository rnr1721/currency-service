<?php

namespace rnr1721\CurrencyService\Providers;

use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use rnr1721\CurrencyService\Exceptions\CurrencyProviderException;

class OpenExchangeRatesProvider implements CurrencyRateProviderInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $apiKey
    ) {
    }

    /**
     * @inheritDoc
     * @return array<string, float>
     * @throws CurrencyProviderException
     */
    public function getRates(string $baseCurrency): array
    {
        try {
            $response = $this->client->request(
                'GET',
                "https://open.exchangerate-api.com/v6/{$this->apiKey}/latest/{$baseCurrency}"
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['rates']) || !is_array($data['rates'])) {
                throw new CurrencyProviderException(
                    'Invalid response format from OpenExchangeRates API'
                );
            }

            // Check that all values are numbers
            foreach ($data['rates'] as $currency => $rate) {
                if (!is_numeric($rate)) {
                    throw new CurrencyProviderException(
                        "Invalid rate value for currency {$currency}"
                    );
                }
            }

            return $data['rates'];
        } catch (GuzzleException $e) {
            throw new CurrencyProviderException(
                "Failed to fetch rates from OpenExchangeRates: {$e->getMessage()}",
                $e
            );
        } catch (JsonException $e) {
            throw new CurrencyProviderException(
                "Failed to parse OpenExchangeRates response: {$e->getMessage()}",
                $e
            );
        }
    }
}
