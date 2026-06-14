<?php

declare(strict_types=1);

namespace App\Service\StockData;

use Psr\Cache\CacheItemPoolInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Results\Quote;

class YahooFinanceProvider
{
    private ApiClient $client;

    public function __construct(?CacheItemPoolInterface $contextCache = null)
    {
        $this->client = ApiClientFactory::createApiClient(
            clientOptions: [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                ],
            ],
            retries: 5,
            retryDelay: 2000,
            cache: $contextCache,
            cacheTtl: 300,
        );
    }

    public function getQuote(string $ticker): ?Quote
    {
        return $this->client->getQuote($ticker);
    }

    /**
     * @param string[] $tickers
     *
     * @return array<string, Quote|null>
     */
    public function getQuotes(array $tickers): array
    {
        $results = [];

        try {
            $quotes = $this->client->getQuotes($tickers);
            $quoteMap = [];

            foreach ($quotes as $quote) {
                $quoteMap[$quote->getSymbol()] = $quote;
            }

            foreach ($tickers as $ticker) {
                $results[$ticker] = $quoteMap[$ticker] ?? null;
            }

            return $results;
        } catch (\Throwable) {
            foreach ($tickers as $ticker) {
                try {
                    $results[$ticker] = $this->client->getQuote($ticker);
                } catch (\Throwable) {
                    $results[$ticker] = null;
                }
            }
        }

        return $results;
    }
}