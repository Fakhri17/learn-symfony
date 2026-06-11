<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\StockData\YahooFinanceDebugService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-yahoo-finance',
    description: 'Debug/test Yahoo Finance API with Indonesian stocks (.JK suffix)',
)]
class TestYahooFinanceCommand extends Command
{
    private const DEFAULT_TICKERS = [
        'BBCA.JK',
        'BBRI.JK',
        'BMRI.JK',
        'TLKM.JK',
        'ASII.JK',
    ];

    public function __construct(
        private readonly YahooFinanceDebugService $yahooFinanceDebugService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('ticker', null, InputOption::VALUE_REQUIRED, 'Single ticker to test (e.g. BBCA.JK)')
            ->addOption('tickers', null, InputOption::VALUE_REQUIRED, 'Comma-separated tickers (e.g. BBCA.JK,BBRI.JK,TLKM.JK)')
            ->addOption('raw', null, InputOption::VALUE_NONE, 'Dump raw object structure for each quote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Determine tickers
        $tickers = $this->resolveTickers($input);

        if (empty($tickers)) {
            $io->error('No tickers specified. Use --ticker or --tickers option.');

            return Command::FAILURE;
        }

        $showRaw = (bool) $input->getOption('raw');

        $io->title('Testing Yahoo Finance API');

        // Single ticker uses fetchQuoteWithError, multiple use fetchQuotes (with fallback)
        if (count($tickers) === 1) {
            $symbol = $tickers[0];
            $io->text("Fetching single ticker: <info>{$symbol}</info>");

            $result = $this->yahooFinanceDebugService->fetchQuoteWithError($symbol);
            $error = $result['error'] !== null ? new \RuntimeException($result['error']) : null;
            $this->displayQuoteResult($io, $symbol, $result['quote'], $error, $showRaw);
        } else {
            $io->text('Fetching batch tickers: <info>' . implode(', ', $tickers) . '</info>');

            $results = $this->yahooFinanceDebugService->fetchQuotes($tickers);

            foreach ($tickers as $symbol) {
                $quote = $results[$symbol] ?? null;
                $this->displayQuoteResult($io, $symbol, $quote, null, $showRaw);
            }
        }

        $io->newLine();
        $io->success('Yahoo Finance test completed.');

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function resolveTickers(InputInterface $input): array
    {
        $ticker = $input->getOption('ticker');
        $tickers = $input->getOption('tickers');

        if ($ticker) {
            return [$ticker];
        }

        if ($tickers) {
            return array_map('trim', explode(',', $tickers));
        }

        return self::DEFAULT_TICKERS;
    }

    private function displayQuoteResult(SymfonyStyle $io, string $symbol, mixed $quote, ?\Throwable $error, bool $showRaw): void
    {
        $display = $this->yahooFinanceDebugService->buildQuoteDisplay($symbol, $quote, $error);

        $io->section("Ticker: {$symbol}");

        if ($display['status'] === 'FAILED') {
            $io->error("Status: FAILED");
            $io->text("Error: {$display['error']}");

            return;
        }

        $io->text('Status: <info>SUCCESS</info>');
        $io->newLine();

        // Display structured data in a table
        $table = new Table($io);
        $table->setHeaders(['Field', 'Value']);
        $table->setStyle('box');

        foreach ($display['data'] as $field => $value) {
            $table->addRow([$field, $value]);
        }

        $table->render();

        // Show available getters
        if ($display['raw'] !== null) {
            $getters = $this->yahooFinanceDebugService->getAvailableGetters($display['raw']);
            $io->text('Available getters: <comment>' . count($getters) . '</comment> methods');
        }

        // Dump raw object structure
        if ($showRaw && $display['raw'] !== null) {
            $io->section('Raw Object Dump');

            $io->block(var_export($display['raw'], true), 'RAW', 'fg=gray', ' ', true);
        }
    }
}