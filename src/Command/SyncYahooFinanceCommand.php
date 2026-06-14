<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Service\StockData\StockDataSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-yahoo-finance',
    description: 'Sync active company stock data from Yahoo Finance into stock_data_staging.',
)]
class SyncYahooFinanceCommand extends Command
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly StockDataSyncService $stockDataSyncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of active companies to process')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Process only a specific company code, e.g. BBCA')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Fetch data but do not save staging rows or sync log');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = $input->getOption('code');
        $limit = $this->parseLimit($input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');

        $companies = $this->findCompanies($code, $limit);

        if ($companies === []) {
            $io->warning('No active companies found for sync.');

            return Command::SUCCESS;
        }

        $io->title('Sync Yahoo Finance');

        if ($dryRun) {
            $io->warning('Dry-run mode enabled. No database changes will be saved.');
        }

        $syncResult = $this->stockDataSyncService->sync($companies, $dryRun);

        foreach ($syncResult['results'] as $result) {
            $ticker = $result['ticker'] !== '' ? $result['ticker'] : $result['code'];
            $io->text(sprintf('Processing %s...', $ticker));

            if ($result['status'] === 'failed') {
                $io->text(sprintf('FAILED %s error=%s', $result['code'], $result['error'] ?? 'Unknown error'));
                $io->newLine();

                continue;
            }

            $message = sprintf(
                '%s %s price=%s volume=%s',
                strtoupper($result['status']),
                $result['code'],
                $result['price'] !== null ? (string) $result['price'] : 'null',
                $result['volume'] ?? 'null',
            );

            if ($result['missingFields'] !== []) {
                $message .= sprintf(' missing=%s', implode(',', $result['missingFields']));
            }

            $io->text($message);
            $io->newLine();
        }

        $io->definitionList(
            ['success' => (string) $syncResult['totalSuccess']],
            ['partial' => (string) $syncResult['totalPartial']],
            ['failed' => (string) $syncResult['totalFailed']],
        );

        $io->success($dryRun ? 'Yahoo Finance dry-run sync completed.' : 'Yahoo Finance sync completed.');

        return Command::SUCCESS;
    }

    /**
     * @return Company[]
     */
    private function findCompanies(?string $code, ?int $limit): array
    {
        $criteria = ['isActive' => true];

        if (is_string($code) && $code !== '') {
            $criteria['code'] = strtoupper(trim($code));
        }

        return $this->companyRepository->findBy($criteria, ['code' => 'ASC'], $limit);
    }

    private function parseLimit(mixed $limit): ?int
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        $parsed = (int) $limit;

        return $parsed > 0 ? $parsed : null;
    }
}