<?php

require_once __DIR__ . "/../../internal/model/Asset.php";
require_once __DIR__ . "/../../internal/validator/AssetValidator.php";

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception('Assertion failed: ' . $msg);
    }
}

function assertFalse($cond, $msg = '') {
    assertTrue(!$cond, $msg);
}

function runAssetValidationTests() {
    $tests = [
        [
            'name' => 'valid domain asset',
            'asset' => (object)['name' => 'example.com', 'type' => 'domain'],
            'wantErr' => false
        ],
        [
            'name' => 'valid ip asset',
            'asset' => (object)['name' => '127.0.0.1', 'type' => 'ip'],
            'wantErr' => false
        ],
        [
            'name' => 'invalid asset type',
            'asset' => (object)['name' => 'test', 'type' => 'invalid'],
            'wantErr' => true
        ],
        [
            'name' => 'invalid domain name',
            'asset' => (object)['name' => 'invalid_domain', 'type' => 'domain'],
            'wantErr' => true
        ],
        [
            'name' => 'invalid ip format',
            'asset' => (object)['name' => '256.256.256.256', 'type' => 'ip'],
            'wantErr' => true
        ]
    ];

    foreach ($tests as $tt) {
        $name = $tt['name'];
        $asset = $tt['asset'];
        $wantErr = $tt['wantErr'];

        $error = false;

        try {
            if (!AssetType::isValid($asset->type)) {
                throw new Exception('invalid type');
            }

            if (!AssetValidator::validateNameByType($asset->name, $asset->type)) {
                throw new Exception('invalid name');
            }
        } catch (Exception $e) {
            $error = true;
        }

        if ($wantErr) {
            assertTrue($error, "[$name] expected error");
        } else {
            assertFalse($error, "[$name] did not expect error");
        }
    }

    echo "Asset validation tests passed\n";
}

try {
    runAssetValidationTests();
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
