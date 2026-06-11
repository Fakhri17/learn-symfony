<?php

declare(strict_types=1);

namespace App\Service\StockData;

use Psr\Cache\CacheItemPoolInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\Quote;

class YahooFinanceDebugService
{
    private ApiClient $client;

    /**
     * Field definitions for display output.
     *
     * Each entry: [label, getterMethod, isDateTime]
     * - If the getter doesn't exist on the Quote object, the value will be "N/A (no getter)".
     * - If the getter returns null, the value will be "N/A".
     * - DateTimeInterface values are formatted as ISO 8601 string.
     */
    private const DISPLAY_FIELDS = [
        ['Price', 'getRegularMarketPrice'],
        ['Open', 'getRegularMarketOpen'],
        ['High', 'getRegularMarketDayHigh'],
        ['Low', 'getRegularMarketDayLow'],
        ['Previous Close', 'getRegularMarketPreviousClose'],
        ['Change', 'getRegularMarketChange'],
        ['Change %', 'getRegularMarketChangePercent'],
        ['Volume', 'getRegularMarketVolume'],
        ['Currency', 'getCurrency'],
        ['Market Time', 'getRegularMarketTime', true],
        ['Exchange', 'getFullExchangeName'],
        ['Market State', 'getMarketState'],
        ['Long Name', 'getLongName'],
        ['Short Name', 'getShortName'],
        ['Trailing PE', 'getTrailingPE'],
        ['Forward PE', 'getForwardPE'],
        ['EPS (Forward)', 'getEpsForward'],
        ['EPS (TTM)', 'getEpsTrailingTwelveMonths'],
        ['Market Cap', 'getMarketCap'],
        ['Book Value', 'getBookValue'],
        ['Price/Book', 'getPriceToBook'],
        ['52 Week High', 'getFiftyTwoWeekHigh'],
        ['52 Week Low', 'getFiftyTwoWeekLow'],
        ['50 Day Avg', 'getFiftyDayAverage'],
        ['200 Day Avg', 'getTwoHundredDayAverage'],
        ['Dividend Rate', 'getTrailingAnnualDividendRate'],
        ['Dividend Yield', 'getTrailingAnnualDividendYield'],
        ['Shares Outstanding', 'getSharesOutstanding'],
        ['Avg Vol (10d)', 'getAverageDailyVolume10Day'],
        ['Avg Vol (3m)', 'getAverageDailyVolume3Month'],
    ];

    /**
     * @param CacheItemPoolInterface|null $contextCache PSR-6 cache for Yahoo Finance session context
     *                                                  (cookies/crumb). Highly recommended to avoid
     *                                                  rate-limiting (429) on the crumb endpoint.
     */
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

    /**
     * Fetch quotes for multiple symbols using getQuotes().
     *
     * Falls back to individual getQuote() calls if batch fetch throws an exception.
     *
     * @param string[] $symbols
     * @return array<string, Quote|null>  Symbol => Quote (or null if failed)
     */
    public function fetchQuotes(array $symbols): array
    {
        $results = [];

        try {
            $quotes = $this->client->getQuotes($symbols);
            // Build a map: symbol => Quote
            $quoteMap = [];
            foreach ($quotes as $quote) {
                $quoteMap[$quote->getSymbol()] = $quote;
            }
            foreach ($symbols as $symbol) {
                $results[$symbol] = $quoteMap[$symbol] ?? null;
            }
        } catch (\Throwable $e) {
            // Fallback: fetch one by one
            foreach ($symbols as $symbol) {
                try {
                    $results[$symbol] = $this->client->getQuote($symbol);
                } catch (\Throwable $innerE) {
                    $results[$symbol] = null;
                }
            }
        }

        return $results;
    }

    /**
     * Fetch a single quote.
     *
     * @return array{quote: Quote|null, error: string|null}
     */
    public function fetchQuoteWithError(string $symbol): array
    {
        try {
            $quote = $this->client->getQuote($symbol);

            return ['quote' => $quote, 'error' => null];
        } catch (\Throwable $e) {
            return ['quote' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Safely call a getter on the Quote object.
     *
     * Returns the raw value if available, or "N/A" if the method doesn't exist,
     * or null-signalling string if the value is null.
     */
    public function safeGet(Quote $quote, string $method): mixed
    {
        if (!method_exists($quote, $method)) {
            return 'N/A (no getter)';
        }

        $value = $quote->{$method}();

        return $value;
    }

    /**
     * Normalize a value for display: convert null/empty to "N/A".
     */
    public function normalize(mixed $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        if ($value === 'N/A (no getter)') {
            return 'N/A (no getter)';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s T');
        }

        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Get all available getter methods from a Quote object.
     *
     * @return string[]
     */
    public function getAvailableGetters(Quote $quote): array
    {
        $methods = [];
        $reflection = new \ReflectionClass($quote);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (str_starts_with($name, 'get') && $method->getNumberOfRequiredParameters() === 0) {
                $methods[] = $name;
            }
        }

        sort($methods);

        return $methods;
    }

    /**
     * Build a display array for a single Quote result.
     *
     * @return array{status: string, data: array<string, string>, raw: Quote|null, error: string|null}
     */
    public function buildQuoteDisplay(string $symbol, ?Quote $quote, ?\Throwable $error = null): array
    {
        if ($quote === null || $error !== null) {
            return [
                'status' => 'FAILED',
                'data' => [],
                'raw' => null,
                'error' => $error?->getMessage() ?? 'No data returned',
            ];
        }

        $data = [];
        foreach (self::DISPLAY_FIELDS as $field) {
            [$label, $getter] = $field;
            $value = $this->safeGet($quote, $getter);
            $data[$label] = $this->normalize($value);
        }

        return [
            'status' => 'SUCCESS',
            'data' => $data,
            'raw' => $quote,
            'error' => null,
        ];
    }
}