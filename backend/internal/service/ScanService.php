<?php

class ScanService
{
    private $assetRepo;
    private $scanRepo;

    private $subdomainScanner;
    private $whoisScanner;
    private $dnsScanner;
    private $ipScanner;
    private $portScanner;
    private $sslScanner;
    private $techScanner;
    private $certTransScanner;

    public function __construct($assetRepo, $scanRepo)
    {
        $this->assetRepo = $assetRepo;
        $this->scanRepo = $scanRepo;

        $this->subdomainScanner = new SubdomainScanner();
        $this->whoisScanner = new WHOISScanner();
        $this->dnsScanner = new DNSScanner();
        $this->ipScanner = new IPScanner();
        $this->portScanner = new PortScanner();
        $this->sslScanner = new SSLScanner();
        $this->techScanner = new TechScanner();
        $this->certTransScanner = new CertTransScanner();
    }

    private function uuid(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function startScan($assetId, $scanType)
    {
        $asset = $this->assetRepo->getById($assetId);

        if (!$asset) {
            throw new Exception("Asset not found");
        }

        $supported = ["subdomain", "dns", "whois", "ip", "port", "ssl", "tech", "cert_trans", "asn", "all"];

        // delete old job for same asset/type so scan result is replaced
        $this->scanRepo->deleteScanJobsByAssetAndType($assetId, $scanType);
        if (!in_array($scanType, $supported, true)) {
            throw new Exception("Unsupported scan type. Supported: subdomain, dns, whois, ip, port, ssl, tech, cert_trans, asn, all");
        }

        $job = [
            "id" => $this->uuid(),
            "asset_id" => $assetId,
            "scan_type" => $scanType,
            "status" => "pending",
            "results" => 0,
            "error" => null,
            "started_at" => null,
            "ended_at" => null,
            "created_at" => date("Y-m-d H:i:s")
        ];

        $this->scanRepo->createScanJob($job);

        $started = $this->dispatchWorker($job["id"]);

        if (!$started) {
            // fallback for environments where background process spawning is blocked
            $this->runJob($job["id"]);
        } else {
            // On some Windows / shared hosts, start /B may report success but worker never runs.
            // Check quickly and run locally if still pending.
            usleep(100000); // 0.1s
            $jobStatus = $this->scanRepo->getJobById($job["id"]);
            if ($jobStatus && $jobStatus["status"] === "pending") {
                $this->runJob($job["id"]);
            }
        }

        return $job;
    }

    private function dispatchWorker(string $jobId): bool
    {
        $worker = __DIR__ . "/../scan_worker.php";
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . " " . escapeshellarg($worker) . " " . escapeshellarg($jobId);

        if (stripos(PHP_OS, 'WIN') === 0) {
            // for Windows, use start /B with a dummy title to avoid first arg being taken as title
            $winCmd = "start /B \"\" " . $cmd;
            $process = @popen($winCmd, "r");
            if (!is_resource($process)) {
                return false;
            }
            pclose($process);
            return true;
        } else {
            $output = [];
            $returnVar = null;
            exec($cmd . " > /dev/null 2>&1 &", $output, $returnVar);
            return $returnVar === 0;
        }
    }

    public function runJob(string $jobId): void
    {
        $job = $this->scanRepo->getJobById($jobId);

        if (!$job) {
            throw new Exception("Scan job not found");
        }

        if ($job["status"] !== "pending") {
            return;
        }

        $asset = $this->assetRepo->getById($job["asset_id"]);

        if (!$asset) {
            $this->scanRepo->failJob($jobId, "Asset not found");
            return;
        }

        $this->scanRepo->updateStatus($jobId, "running");

        try {
            $results = 0;
            if ($job["scan_type"] === "subdomain") {
                $this->scanRepo->deleteSubdomainsByAsset($asset["id"]);
                $found = $this->subdomainScanner->scan($asset["name"]);

                foreach ($found as $subdomain) {
                    $this->scanRepo->saveSubdomain([
                        "id" => $this->uuid(),
                        "asset_id" => $asset["id"],
                        "scan_job_id" => $jobId,
                        "name" => $subdomain["name"],
                        "source" => $subdomain["source"],
                        "is_active" => $subdomain["is_active"]
                    ]);
                }

                $results = count($found);
            } elseif ($job["scan_type"] === "whois") {
                $this->scanRepo->deleteWHOISByAsset($asset["id"]);
                $record = $this->whoisScanner->scan($asset["name"]);
                $this->scanRepo->saveWHOIS($jobId, $asset["id"], $record);
                $results = 1;
            } elseif ($job["scan_type"] === "dns") {
                $this->scanRepo->deleteDNSByAsset($asset["id"]);
                $records = $this->dnsScanner->scan($asset["name"]);

                foreach ($records as $r) {
                    $this->scanRepo->saveDNSRecord([
                        "asset_id" => $asset["id"],
                        "scan_job_id" => $jobId,
                        "record_type" => $r["type"],
                        "name" => $r["name"],
                        "value" => $r["value"],
                        "ttl" => $r["ttl"]
                    ]);
                }

                $results = count($records);
            } elseif ($job["scan_type"] === "ip") {
                if ($asset["type"] !== "ip") {
                    throw new Exception("IP scan only supports asset type 'ip'");
                }

                $this->scanRepo->deleteIPResultsByAsset($asset["id"]);
                $entries = $this->ipScanner->scan($asset["name"]);
                foreach ($entries as $entry) {
                    $this->scanRepo->saveIPResult([
                        "id" => $this->uuid(),
                        "scan_job_id" => $jobId,
                        "asset_id" => $asset["id"],
                        "ip_address" => $entry["ip_address"],
                        "geolocation" => $entry["geolocation"],
                        "asn" => $entry["asn"],
                        "reverse_dns" => $entry["reverse_dns"],
                        "created_at" => date("Y-m-d H:i:s")
                    ]);
                }

                $results = count($entries);
            } elseif ($job["scan_type"] === "asn") {
                $ip = $asset["name"];

                if ($asset["type"] === "domain") {
                    $ip = gethostbyname($asset["name"]);
                }

                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    throw new Exception("Unable to resolve asset to a valid IP for ASN scan");
                }

                $entries = $this->ipScanner->scan($ip);
                foreach ($entries as $entry) {
                    $this->scanRepo->saveIPResult([
                        "id" => $this->uuid(),
                        "scan_job_id" => $jobId,
                        "asset_id" => $asset["id"],
                        "ip_address" => $entry["ip_address"],
                        "geolocation" => $entry["geolocation"],
                        "asn" => $entry["asn"],
                        "reverse_dns" => $entry["reverse_dns"],
                        "created_at" => $entry["created_at"]
                    ]);
                }
                $results = count($entries);
            } elseif ($job["scan_type"] === "port") {
                $ip = $asset["name"];

                if ($asset["type"] === "domain") {
                    $ip = gethostbyname($asset["name"]);
                }

                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    throw new Exception("Unable to resolve asset to a valid IP for port scan");
                }

                if (!$this->isAllowedScannableIP($ip)) {
                    throw new Exception("Port scan not allowed for this IP address");
                }

                $this->scanRepo->deletePortResultsByAsset($asset["id"]);
                $result = $this->portScanner->scan($ip);
                $this->scanRepo->savePortResult([
                    "id" => $this->uuid(),
                    "scan_job_id" => $jobId,
                    "asset_id" => $asset["id"],
                    "ip_address" => $result["ip_address"],
                    "open_ports" => $result["open_ports"],
                    "closed_ports" => $result["closed_ports"],
                    "total_scanned" => $result["total_scanned"],
                    "scan_duration_ms" => $result["scan_duration_ms"],
                    "created_at" => $result["created_at"]
                ]);

                $results = 1;
            } elseif ($job["scan_type"] === "ssl") {
                if ($asset["type"] !== "domain") {
                    throw new Exception("SSL scan only supports domain asset types");
                }

                $ip = gethostbyname($asset["name"]);
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    throw new Exception("Unable to resolve domain to IP for SSL scan");
                }

                $this->scanRepo->deleteSSLResultsByAsset($asset["id"]);
                $entries = $this->sslScanner->scan($asset["name"]);
                foreach ($entries as $entry) {
                    $this->scanRepo->saveSSLResult([
                        "id" => $this->uuid(),
                        "scan_job_id" => $jobId,
                        "asset_id" => $asset["id"],
                        "domain" => $entry["domain"],
                        "certificate" => $entry["certificate"],
                        "connection" => $entry["connection"],
                        "grade" => $entry["grade"],
                        "issues" => $entry["issues"],
                        "created_at" => $entry["created_at"]
                    ]);
                }

                $results = count($entries);
            } elseif ($job["scan_type"] === "tech") {
                if ($asset["type"] !== "domain") {
                    throw new Exception("Technology scan only supports domain asset types");
                }

                $ip = gethostbyname($asset["name"]);
                if (!filter_var($ip, FILTER_VALIDATE_IP) || !$this->isAllowedScannableIP($ip)) {
                    throw new Exception("Technology scan not allowed for this IP address");
                }

                $this->scanRepo->deleteTechResultsByAsset($asset["id"]);
                $entries = $this->techScanner->scan($asset["name"]);
                foreach ($entries as $entry) {
                    $this->scanRepo->saveTechResult([
                        "id" => $this->uuid(),
                        "scan_job_id" => $jobId,
                        "asset_id" => $asset["id"],
                        "domain" => $entry["domain"],
                        "technologies" => $entry["technologies"],
                        "headers" => $entry["headers"],
                        "meta_tags" => $entry["meta_tags"],
                        "created_at" => $entry["created_at"]
                    ]);
                }

                $results = count($entries);
            } elseif ($job["scan_type"] === "cert_trans") {
                if ($asset["type"] !== "domain") {
                    throw new Exception("Certificate transparency scan only supports domain asset types");
                }

                $this->scanRepo->deleteCertTransResultsByAsset($asset["id"]);
                $entries = $this->certTransScanner->scan($asset["name"]);
                foreach ($entries as $entry) {
                    $this->scanRepo->saveCertTransResult([
                        "id" => $this->uuid(),
                        "scan_job_id" => $jobId,
                        "asset_id" => $asset["id"],
                        "domain" => $entry["domain"],
                        "certificate" => $entry["certificate"],
                        "created_at" => $entry["created_at"]
                    ]);
                }

                $results = count($entries);
            } elseif ($job["scan_type"] === "all") {
                // All passive scans for domain
                if ($asset["type"] !== "domain") {
                    throw new Exception("'all' scan only supports domain assets");
                }

                $partial = false;
                $results = 0;

                try {
                    $results += $this->runSubdomainScan($asset, $jobId);
                } catch (Exception $e) {
                    $partial = true;
                }

                try {
                    $results += $this->runDNSScan($asset, $jobId);
                } catch (Exception $e) {
                    $partial = true;
                }

                try {
                    $results += $this->runWhoisScan($asset, $jobId);
                } catch (Exception $e) {
                    $partial = true;
                }

                try {
                    $results += $this->runSSLScan($asset, $jobId);
                } catch (Exception $e) {
                    $partial = true;
                }

                try {
                    $results += $this->runTechScan($asset, $jobId);
                } catch (Exception $e) {
                    $partial = true;
                }

                try {
                    $results += $this->runCertTransScan($asset, $jobId);
                } catch (Exception $e) {
                    $partial = true;
                }

                if ($partial) {
                    $this->scanRepo->updateStatus($jobId, 'partial');
                }
            }

            if (isset($partial) && $partial === true) {
                $this->scanRepo->completeJob($jobId, $results, 'partial');
            } else {
                $this->scanRepo->completeJob($jobId, $results, 'completed');
            }
        } catch (Exception $e) {
            $this->scanRepo->failJob($jobId, $e->getMessage());
        }
    }

    private function runSubdomainScan($asset, $jobId): int
    {
        $this->scanRepo->deleteSubdomainsByAsset($asset["id"]);
        $found = $this->subdomainScanner->scan($asset["name"]);

        foreach ($found as $subdomain) {
            $this->scanRepo->saveSubdomain([
                "id" => $this->uuid(),
                "asset_id" => $asset["id"],
                "scan_job_id" => $jobId,
                "name" => $subdomain["name"],
                "source" => $subdomain["source"],
                "is_active" => $subdomain["is_active"]
            ]);
        }

        return count($found);
    }

    private function runDNSScan($asset, $jobId): int
    {
        $records = $this->dnsScanner->scan($asset["name"]);

        foreach ($records as $r) {
            $this->scanRepo->saveDNSRecord([
                "asset_id" => $asset["id"],
                "scan_job_id" => $jobId,
                "record_type" => $r["type"],
                "name" => $r["name"],
                "value" => $r["value"],
                "ttl" => $r["ttl"]
            ]);
        }

        return count($records);
    }

    private function runWhoisScan($asset, $jobId): int
    {
        $record = $this->whoisScanner->scan($asset["name"]);
        $this->scanRepo->saveWHOIS($jobId, $asset["id"], $record);
        return 1;
    }

    private function runSSLScan($asset, $jobId): int
    {
        if ($asset["type"] !== "domain") {
            throw new Exception("SSL scan only supports domain asset types");
        }

        $ip = gethostbyname($asset["name"]);
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !$this->isAllowedScannableIP($ip)) {
            throw new Exception("SSL scan not allowed for this IP address");
        }

        $entries = $this->sslScanner->scan($asset["name"]);

        foreach ($entries as $entry) {
            $this->scanRepo->saveSSLResult([
                "id" => $this->uuid(),
                "scan_job_id" => $jobId,
                "asset_id" => $asset["id"],
                "domain" => $entry["domain"],
                "certificate" => $entry["certificate"],
                "connection" => $entry["connection"],
                "grade" => $entry["grade"],
                "issues" => $entry["issues"],
                "created_at" => $entry["created_at"]
            ]);
        }

        return count($entries);
    }

    private function runTechScan($asset, $jobId): int
    {
        if ($asset["type"] !== "domain") {
            throw new Exception("Technology scan only supports domain asset types");
        }

        $ip = gethostbyname($asset["name"]);
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !$this->isAllowedScannableIP($ip)) {
            throw new Exception("Technology scan not allowed for this IP address");
        }

        $entries = $this->techScanner->scan($asset["name"]);

        foreach ($entries as $entry) {
            $this->scanRepo->saveTechResult([
                "id" => $this->uuid(),
                "scan_job_id" => $jobId,
                "asset_id" => $asset["id"],
                "domain" => $entry["domain"],
                "technologies" => $entry["technologies"],
                "headers" => $entry["headers"],
                "meta_tags" => $entry["meta_tags"],
                "created_at" => $entry["created_at"]
            ]);
        }

        return count($entries);
    }

    private function runCertTransScan($asset, $jobId): int
    {
        if ($asset["type"] !== "domain") {
            throw new Exception("Certificate transparency scan only supports domain asset types");
        }

        $entries = $this->certTransScanner->scan($asset["name"]);

        foreach ($entries as $entry) {
            $this->scanRepo->saveCertTransResult([
                "id" => $this->uuid(),
                "scan_job_id" => $jobId,
                "asset_id" => $asset["id"],
                "domain" => $entry["domain"],
                "certificate" => $entry["certificate"],
                "created_at" => $entry["created_at"]
            ]);
        }

        return count($entries);
    }

    public function getScanJob($id)
    {
        $job = $this->scanRepo->getJobById($id);
        if (!$job) {
            throw new Exception("Scan job not found");
        }
        return $job;
    }

    public function getScansByAsset($assetId): array
    {
        return $this->scanRepo->getByAsset($assetId);
    }

    public function getAllResultsByAsset($assetId): array
    {
        $scanJobs = $this->getScansByAsset($assetId);

        // Keep latest job per scan_type (replace behavior)
        $latestByType = [];
        foreach ($scanJobs as $job) {
            $type = $job['scan_type'];
            if (!isset($latestByType[$type])) {
                $latestByType[$type] = $job;
                continue;
            }

            $existing = $latestByType[$type];
            if (strtotime($job['created_at']) > strtotime($existing['created_at'])) {
                $latestByType[$type] = $job;
            }
        }

        $results = [];
        foreach ($latestByType as $job) {
            $results[] = $this->getScanResults($job['id']);
        }

        return $results;
    }

    public function getScanResults($scanId): array
    {
        $job = $this->getScanJob($scanId);

        $rows = $this->scanRepo->getResults($scanId);

        return [
            'job_id' => $scanId,
            'scan_type' => $job['scan_type'],
            'status' => $job['status'],
            'started_at' => $job['started_at'],
            'ended_at' => $job['ended_at'],
            'created_at' => $job['created_at'],
            'results' => $rows
        ];
    }

    private function isAllowedScannableIP(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $segments = explode('.', $ip);
            $first = (int)$segments[0];
            $second = (int)$segments[1];

            if ($first === 10) {
                return true;
            }

            if ($first === 172 && $second >= 16 && $second <= 31) {
                return true;
            }

            if ($first === 192 && $second === 168) {
                return true;
            }
        }

        return false;
    }

    public function getSubdomains($assetId): array
    {
        return $this->scanRepo->getSubdomainsByAsset($assetId);
    }

    public function getWhois($assetId): array
    {
        return $this->scanRepo->getWHOISByAsset($assetId);
    }

    public function getDNS($assetId): array
    {
        return $this->scanRepo->getDNSRecordsByAsset($assetId);
    }
}
