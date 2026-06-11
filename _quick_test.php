<?php
require __DIR__ . '/vendor/autoload.php';

use Scheb\YahooFinanceApi\ApiClientFactory;

echo "Creating API client with retry...\n";
$client = ApiClientFactory::createApiClient(retries: 3, retryDelay: 1000);

echo "Testing getQuote('BBCA.JK')...\n";
try {
    $quote = $client->getQuote('BBCA.JK');
    if ($quote === null) {
        echo "Result: NULL (symbol not found or no data)\n";
    } else {
        echo "SUCCESS! Symbol: " . $quote->getSymbol() . "\n";
        echo "Price: " . $quote->getRegularMarketPrice() . "\n";
        echo "Name: " . $quote->getLongName() . "\n";
    }
} catch (\Throwable $e) {
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
}

echo "\n---\nTesting getQuotes(['BBCA.JK'])...\n";
try {
    $quotes = $client->getQuotes(['BBCA.JK']);
    echo "Got " . count($quotes) . " quotes\n";
    foreach ($quotes as $q) {
        echo "Symbol: " . $q->getSymbol() . " | Price: " . $q->getRegularMarketPrice() . "\n";
    }
} catch (\Throwable $e) {
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}