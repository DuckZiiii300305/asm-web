<?php

require_once __DIR__ . "/../../internal/scanner/TechScanner.php";

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function runTechScannerTests() {
    $scanner = new TechScanner();

    $result = $scanner->scan('example.com');

    assertTrue(is_array($result), 'Tech scan should return array');
    assertTrue(count($result) === 1, 'Tech scan should return one entry');

    $entry = $result[0];
    assertTrue(isset($entry['domain']) && $entry['domain'] === 'example.com', 'domain matches');
    assertTrue(isset($entry['technologies']), 'technologies exists');
    assertTrue(isset($entry['headers']), 'headers exists');
    assertTrue(isset($entry['meta_tags']), 'meta_tags exists');

    echo "Tech scanner tests passed\n";
}

try {
    runTechScannerTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
