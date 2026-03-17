<?php

require_once __DIR__ . '/../../internal/service/AssetService.php';
require_once __DIR__ . '/../../internal/model/Asset.php';
require_once __DIR__ . '/../../internal/model/Errors.php';
require_once __DIR__ . '/../../internal/validator/AssetValidator.php';

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

class MockAssetRepo
{
    public $createdAsset = null;
    public $getByIdMap = [];

    public function create($asset) {
        $this->createdAsset = $asset;
    }

    public function list($page, $limit, $type, $status, $search, $sortBy, $sortOrder) {
        return ['page' => $page, 'limit' => $limit];
    }

    public function getById($id) {
        return $this->getByIdMap[$id] ?? null;
    }

    public function update($id, $asset) {
        $this->getByIdMap[$id] = $asset;
    }

    public function delete($id) {
        unset($this->getByIdMap[$id]);
    }

    public function stats() { return (object)['toArray' => function(){ return []; }]; }
    public function count($type,$status){ return 2; }
}

function runAssetServiceTests() {
    $repo = new MockAssetRepo();
    $service = new AssetService($repo);

    // create valid asset
    $asset = $service->create(['name' => 'example.com', 'type' => 'domain']);
    assertTrue($asset->name === 'example.com', 'created asset name is correct');
    assertTrue($asset->type === 'domain', 'created asset type is domain');
    assertTrue($repo->createdAsset instanceof Asset, 'repository create called');

    // list with invalid type should throw
    $error = false;
    try {
        $service->list(1, 10, 'badtype', null, null, 'created_at', 'desc');
    } catch (Exception $e) {
        $error = true;
    }
    assertTrue($error, 'Invalid type should throw error');

    echo "Asset service tests passed\n";
}

try {
    runAssetServiceTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
