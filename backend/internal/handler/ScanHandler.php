<?php

class ScanHandler
{
    private $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function start($assetId)
    {
        try {
            $body = file_get_contents("php://input");
            $data = [];
            if (!empty($body)) {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }

            $scanType = $data["scan_type"] ?? $_GET["scan_type"] ?? "subdomain";

            $job = $this->service->startScan($assetId, $scanType);
            Response::json($job, 202);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function listByAsset($assetId)
    {
        try {
            $jobs = $this->service->getScansByAsset($assetId);
            Response::json($jobs);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getResults($scanId)
    {
        try {
            $results = $this->service->getScanResults($scanId);
            Response::json($results);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function status($id)
    {
        try {
            $job = $this->service->getScanJob($id);
            Response::json($job);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function assetDNS($assetId)
    {
        try {
            $rows = $this->service->getDNS($assetId);
            Response::json($rows);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function assetWHOIS($assetId)
    {
        try {
            $rows = $this->service->getWhois($assetId);
            Response::json($rows);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function assetSubdomains($assetId)
    {
        try {
            $rows = $this->service->getSubdomains($assetId);
            Response::json($rows);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function assetResults($assetId)
    {
        try {
            $rows = $this->service->getAllResultsByAsset($assetId);
            Response::json($rows);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}