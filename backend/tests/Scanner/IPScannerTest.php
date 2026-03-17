<?php

require_once __DIR__ . "/../../internal/scanner/IPScanner.php";

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function runIPScannerTests() {
    $scanner = new IPScanner();

    $results = $scanner->scan('127.0.0.1');

    assertTrue(is_array($results), 'IP scan should return array');
    assertTrue(count($results) === 1, 'IP scan should return exactly one entry');

    $entry = $results[0];
    assertTrue(isset($entry['ip_address']) && $entry['ip_address'] === '127.0.0.1', 'ip_address should be 127.0.0.1');
    assertTrue(isset($entry['geolocation']), 'geolocation field exists');
    assertTrue(isset($entry['asn']), 'asn field exists');

    echo "IP scanner tests passed\n";
}

try {
    runIPScannerTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
