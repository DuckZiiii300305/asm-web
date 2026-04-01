<?php
$testFiles = [
    __DIR__ . '/Model/AssetTest.php',
    __DIR__ . '/Scanner/DNSScannerTest.php',
    __DIR__ . '/Scanner/IPScannerTest.php',
    __DIR__ . '/Scanner/PortScannerTest.php',
    __DIR__ . '/Scanner/SSLScannerTest.php',
    __DIR__ . '/Scanner/Test.php',
    __DIR__ . '/handler/asset_handler_test.php',
    __DIR__ . '/service/asset_service_test.php'
];

foreach ($testFiles as $file) {
    echo "Running $file ...\n";
    $code = php_exec($file);
    echo $code;
    echo "\n";
}

function php_exec($file) {
    $phpBin = 'c:/xampp/php/php.exe';
    if (!file_exists($phpBin)) {
        $phpBin = 'php';
    }

    $output = null;
    $return = null;
    exec(escapeshellarg($phpBin) . ' ' . escapeshellarg($file) . ' 2>&1', $output, $return);
    return implode("\n", $output) . "\nExit code: $return\n";
}
