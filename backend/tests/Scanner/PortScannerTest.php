<?php

require_once __DIR__ . "/../../internal/scanner/PortScanner.php";

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function runPortScannerTests() {
    $scanner = new PortScanner();
    $result = $scanner->scan('127.0.0.1', 1, 1024);

    assertTrue(is_array($result), 'Port scan should return array');
    assertTrue(isset($result['ip_address']), 'result has ip_address');
    assertTrue(isset($result['open_ports']), 'result has open_ports');
    assertTrue(isset($result['closed_ports']), 'result has closed_ports');
    assertTrue(isset($result['total_scanned']), 'result has total_scanned');

    echo "Port scanner tests passed\n";
}

try {
    runPortScannerTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
