<?php

require_once __DIR__ . "/../../internal/scanner/DNSScanner.php";

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function assertNotEmpty($var, $msg = '') {
    if (empty($var)) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function runDNSScannerTests() {
    $scanner = new DNSScanner();

    $results = $scanner->scan('example.com');
    assertTrue(is_array($results), 'DNS scan should return array');

    if (!empty($results)) {
        assertNotEmpty($results[0]['type'], 'DNS record must have type');
        assertNotEmpty($results[0]['name'], 'DNS record must have name');
    }

    echo "DNS scanner tests passed\n";
}

try {
    runDNSScannerTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
