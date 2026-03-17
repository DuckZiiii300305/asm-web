<?php

interface ScanRepository
{
    public function createScanJob(array $job): void;
    public function getJobById(string $id): ?array;
    public function updateStatus(string $id, string $status): void;
    public function completeJob(string $id, int $results, string $status = 'completed'): void;
    public function failJob(string $id, string $error): void;

    public function getByAsset(string $assetId): array;
    public function deleteScanJobsByAssetAndType(string $assetId, string $scanType): void;

    public function saveSubdomain(array $data): void;
    public function deleteSubdomainsByAsset(string $assetId): void;
    public function getSubdomainsByAsset(string $assetId): array;

    public function saveWHOIS(string $scanId, string $assetId, array $record): void;
    public function deleteWHOISByAsset(string $assetId): void;
    public function getWHOISByAsset(string $assetId): array;

    public function saveDNSRecord(array $data): void;
    public function deleteDNSByAsset(string $assetId): void;
    public function getDNSRecordsByAsset(string $assetId): array;

    public function saveIPResult(array $data): void;
    public function deleteIPResultsByAsset(string $assetId): void;
    public function getIPResultsByJob(string $scanId): array;

    public function savePortResult(array $data): void;
    public function getPortResultsByJob(string $scanId): array;

    public function saveSSLResult(array $data): void;
    public function getSSLResultsByJob(string $scanId): array;

    public function saveTechResult(array $data): void;
    public function deleteTechResultsByAsset(string $assetId): void;
    public function getTechResultsByJob(string $scanId): array;

    public function saveCertTransResult(array $data): void;
    public function deleteCertTransResultsByAsset(string $assetId): void;
    public function getCertTransResultsByJob(string $scanId): array;

    public function getResults(string $scanId): array;
}
