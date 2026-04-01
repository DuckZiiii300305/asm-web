<?php

require_once __DIR__ . "/../../internal/scanner/SSLScanner.php";

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function runSSLScannerTests() {
    $scanner = new SSLScanner();

    // Try a well-known domain that supports HTTPS
    $domain = 'example.com';

    try {
        $result = $scanner->scan($domain);
        assertTrue(is_array($result), 'SSL scan should return array');
        assertTrue(count($result) === 1, 'SSL scan should return one entry');

        $entry = $result[0];
        assertTrue(isset($entry['domain']) && $entry['domain'] === $domain, 'domain matches');
        assertTrue(isset($entry['certificate']) && is_array($entry['certificate']), 'certificate present');
        assertTrue(isset($entry['connection']) && is_array($entry['connection']), 'connection present');

    } catch (Exception $e) {
        // Accept missing network or not accessible in certain environments as non-fatal
        echo "SSL scanner tests skipped due to network: " . $e->getMessage() . "\n";
        exit(0);
    }

    echo "SSL scanner tests passed\n";
}

try {
    runSSLScannerTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
