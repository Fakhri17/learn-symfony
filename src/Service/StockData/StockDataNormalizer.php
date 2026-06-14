<?php

declare(strict_types=1);

namespace App\Service\StockData;

use Scheb\YahooFinanceApi\Results\Quote;

class StockDataNormalizer
{
    /**
     * @return array{
     *     price: ?float,
     *     openPrice: ?float,
     *     highPrice: ?float,
     *     lowPrice: ?float,
     *     volume: ?string,
     *     peRatio: ?float,
     *     eps: ?float,
     *     marketCap: ?float,
     *     currency: ?string,
     *     marketTime: ?\DateTimeImmutable,
     *     rawPayload: array<string, mixed>,
     *     missingFields: string[]
     * }
     */
    public function normalize(Quote $quote): array
    {
        $fields = [
            'price' => 'getRegularMarketPrice',
            'openPrice' => 'getRegularMarketOpen',
            'highPrice' => 'getRegularMarketDayHigh',
            'lowPrice' => 'getRegularMarketDayLow',
            'volume' => 'getRegularMarketVolume',
            'peRatio' => 'getTrailingPE',
            'eps' => 'getEpsTrailingTwelveMonths',
            'marketCap' => 'getMarketCap',
            'currency' => 'getCurrency',
            'marketTime' => 'getRegularMarketTime',
        ];

        $normalized = [];
        $missingFields = [];
        $rawPayload = [];

        foreach ($fields as $field => $getter) {
            $value = $this->safeGet($quote, $getter);

            if ($value['missing']) {
                $missingFields[] = $field;
            }

            $normalized[$field] = $this->normalizeValue($field, $value['value']);
            $rawPayload[$field] = $this->normalizeRawValue($value['value']);
        }

        $rawPayload['symbol'] = $this->normalizeRawValue($this->safeGet($quote, 'getSymbol')['value']);
        $rawPayload['longName'] = $this->normalizeRawValue($this->safeGet($quote, 'getLongName')['value']);
        $rawPayload['shortName'] = $this->normalizeRawValue($this->safeGet($quote, 'getShortName')['value']);
        $rawPayload['quoteType'] = $this->normalizeRawValue($this->safeGet($quote, 'getQuoteType')['value']);
        $rawPayload['marketState'] = $this->normalizeRawValue($this->safeGet($quote, 'getMarketState')['value']);
        $rawPayload['fullExchangeName'] = $this->normalizeRawValue($this->safeGet($quote, 'getFullExchangeName')['value']);

        return [
            'price' => $normalized['price'],
            'openPrice' => $normalized['openPrice'],
            'highPrice' => $normalized['highPrice'],
            'lowPrice' => $normalized['lowPrice'],
            'volume' => $normalized['volume'],
            'peRatio' => $normalized['peRatio'],
            'eps' => $normalized['eps'],
            'marketCap' => $normalized['marketCap'],
            'currency' => $normalized['currency'],
            'marketTime' => $normalized['marketTime'],
            'rawPayload' => $rawPayload,
            'missingFields' => array_values(array_unique($missingFields)),
        ];
    }

    /**
     * @return array{value: mixed, missing: bool}
     */
    private function safeGet(Quote $quote, string $method): array
    {
        if (!method_exists($quote, $method)) {
            return ['value' => null, 'missing' => true];
        }

        $value = $quote->{$method}();

        return ['value' => $value, 'missing' => $value === null];
    }

    private function normalizeValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'marketTime') {
            if ($value instanceof \DateTimeImmutable) {
                return $value;
            }

            if ($value instanceof \DateTimeInterface) {
                return \DateTimeImmutable::createFromInterface($value);
            }

            return null;
        }

        if ($field === 'volume') {
            if (is_int($value) || is_float($value) || is_string($value)) {
                return (string) $value;
            }

            return null;
        }

        if (in_array($field, ['currency'], true)) {
            return (string) $value;
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            return (float) $value;
        }

        return null;
    }

    private function normalizeRawValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }
}