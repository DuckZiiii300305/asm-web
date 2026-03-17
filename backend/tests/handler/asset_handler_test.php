<?php

require_once __DIR__ . '/../../internal/handler/AssetHandler.php';
require_once __DIR__ . '/../../internal/handler/Response.php';
require_once __DIR__ . '/../../internal/model/Errors.php';

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

class MockAssetServiceFail
{
    public function create($data)
    {
        throw new DomainError('invalid test', 400);
    }
}

function runAssetHandlerTests() {
    $service = new MockAssetServiceFail();
    $handler = new AssetHandler($service);

    ob_start();
    $handler->create();
    $body = ob_get_clean();

    assertTrue(strpos($body, '"error"') !== false, 'Response must include error key');

    echo "Asset handler tests passed\n";
}

try {
    runAssetHandlerTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
