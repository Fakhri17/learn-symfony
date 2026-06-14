<?php

declare(strict_types=1);

namespace App\Service\StockData;

use App\Entity\Company;
use App\Entity\DataSourceLog;
use App\Entity\StockDataStaging;
use Doctrine\ORM\EntityManagerInterface;

class StockDataSyncService
{
    private const SOURCE = 'yahoo_finance';
    private const REQUEST_DELAY_MICROSECONDS = 400000;

    public function __construct(
        private readonly YahooFinanceProvider $yahooFinanceProvider,
        private readonly StockDataNormalizer $stockDataNormalizer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param Company[] $companies
     *
     * @return array{
     *     results: array<int, array{code: string, ticker: string, status: string, price: ?float, volume: ?string, missingFields: string[], error: ?string}>,
     *     totalSuccess: int,
     *     totalPartial: int,
     *     totalFailed: int,
     *     log: DataSourceLog
     * }
     */
    public function sync(array $companies, bool $dryRun = false): array
    {
        $log = (new DataSourceLog())
            ->setSource(self::SOURCE)
            ->setStatus('running')
            ->setStartedAt(new \DateTimeImmutable());

        $results = [];
        $totalSuccess = 0;
        $totalPartial = 0;
        $totalFailed = 0;

        foreach ($companies as $index => $company) {
            $ticker = $company->getYahooTicker();
            $code = $company->getCode() ?? 'UNKNOWN';

            if ($ticker === null || $ticker === '') {
                $errorMessage = 'Company has no yahooTicker configured';
                ++$totalFailed;

                $results[] = [
                    'code' => $code,
                    'ticker' => (string) $ticker,
                    'status' => StockDataStaging::STATUS_FAILED,
                    'price' => null,
                    'volume' => null,
                    'missingFields' => ['ticker'],
                    'error' => $errorMessage,
                ];

                if (!$dryRun) {
                    $this->entityManager->persist($this->createFailedStaging($company, $errorMessage));
                }

                continue;
            }

            try {
                $quote = $this->yahooFinanceProvider->getQuote($ticker);

                if ($quote === null) {
                    throw new \RuntimeException('No data returned');
                }

                $normalized = $this->stockDataNormalizer->normalize($quote);
                $status = $this->determineStatus($normalized);

                if ($status === StockDataStaging::STATUS_SUCCESS) {
                    ++$totalSuccess;
                } elseif ($status === StockDataStaging::STATUS_PARTIAL) {
                    ++$totalPartial;
                }

                $results[] = [
                    'code' => $code,
                    'ticker' => $ticker,
                    'status' => $status,
                    'price' => $normalized['price'],
                    'volume' => $normalized['volume'],
                    'missingFields' => $normalized['missingFields'],
                    'error' => null,
                ];

                if (!$dryRun) {
                    $this->entityManager->persist($this->createSuccessStaging($company, $ticker, $status, $normalized));
                }
            } catch (\Throwable $throwable) {
                ++$totalFailed;

                $results[] = [
                    'code' => $code,
                    'ticker' => $ticker,
                    'status' => StockDataStaging::STATUS_FAILED,
                    'price' => null,
                    'volume' => null,
                    'missingFields' => [],
                    'error' => $throwable->getMessage(),
                ];

                if (!$dryRun) {
                    $this->entityManager->persist($this->createFailedStaging($company, $throwable->getMessage(), $ticker));
                }
            }

            if ($index < count($companies) - 1) {
                usleep(self::REQUEST_DELAY_MICROSECONDS);
            }
        }

        $finalStatus = $totalFailed > 0 ? ($totalSuccess > 0 || $totalPartial > 0 ? StockDataStaging::STATUS_PARTIAL : StockDataStaging::STATUS_FAILED) : StockDataStaging::STATUS_SUCCESS;

        $log
            ->setStatus($dryRun ? 'dry_run' : $finalStatus)
            ->setTotalSuccess($totalSuccess)
            ->setTotalPartial($totalPartial)
            ->setTotalFailed($totalFailed)
            ->setMessage(sprintf('Processed %d companies from %s', count($companies), self::SOURCE))
            ->setFinishedAt(new \DateTimeImmutable());

        if (!$dryRun) {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }

        return [
            'results' => $results,
            'totalSuccess' => $totalSuccess,
            'totalPartial' => $totalPartial,
            'totalFailed' => $totalFailed,
            'log' => $log,
        ];
    }

    /**
     * @param array{
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
     * } $normalized
     */
    private function createSuccessStaging(Company $company, string $ticker, string $status, array $normalized): StockDataStaging
    {
        return (new StockDataStaging())
            ->setCompany($company)
            ->setCode($company->getCode())
            ->setTicker($ticker)
            ->setSource(self::SOURCE)
            ->setPrice($normalized['price'])
            ->setOpenPrice($normalized['openPrice'])
            ->setHighPrice($normalized['highPrice'])
            ->setLowPrice($normalized['lowPrice'])
            ->setVolume($normalized['volume'])
            ->setPeRatio($normalized['peRatio'])
            ->setEps($normalized['eps'])
            ->setMarketCap($normalized['marketCap'])
            ->setCurrency($normalized['currency'])
            ->setMarketTime($normalized['marketTime'])
            ->setStatus($status)
            ->setRawPayload($normalized['rawPayload'])
            ->setMissingFields($normalized['missingFields'])
            ->setErrorMessage(null)
            ->setFetchedAt(new \DateTimeImmutable());
    }

    private function createFailedStaging(Company $company, string $errorMessage, ?string $ticker = null): StockDataStaging
    {
        return (new StockDataStaging())
            ->setCompany($company)
            ->setCode($company->getCode())
            ->setTicker($ticker ?? $company->getYahooTicker())
            ->setSource(self::SOURCE)
            ->setStatus(StockDataStaging::STATUS_FAILED)
            ->setRawPayload(null)
            ->setMissingFields([])
            ->setErrorMessage($errorMessage)
            ->setFetchedAt(new \DateTimeImmutable());
    }

    /**
     * @param array{price: ?float, volume: ?string} $normalized
     */
    private function determineStatus(array $normalized): string
    {
        if ($normalized['price'] === null || $normalized['volume'] === null) {
            return StockDataStaging::STATUS_PARTIAL;
        }

        return StockDataStaging::STATUS_SUCCESS;
    }
}