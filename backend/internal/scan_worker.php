<?php

if ($argc < 2) {
    echo "Missing scan job id\n";
    exit(1);
}

$jobId = $argv[1];

require_once __DIR__ . '/bootstrap.php';

$db = getDB();
$assetRepo = new AssetMySQLRepository($db);
$scanRepo = new ScanMySQLRepository($db);
$scanService = new ScanService($assetRepo, $scanRepo);

try {
    $scanService->runJob($jobId);
} catch (Exception $e) {
    // If anything unexpected happens, mark as failed if job exists.
    $job = $scanRepo->getJobById($jobId);
    if ($job) {
        $scanRepo->failJob($jobId, $e->getMessage());
    }
}
