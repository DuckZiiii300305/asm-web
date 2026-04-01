<?php
require_once __DIR__ . '/../ScanRepository.php';
class ScanMySQLRepository implements ScanRepository
{
    private $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function createScanJob(array $job): void
    {
        $stmt = $this->db->prepare("INSERT INTO scan_jobs (id, asset_id, scan_type, status, results, error, started_at, ended_at, created_at) VALUES (:id, :asset_id, :scan_type, :status, :results, :error, :started_at, :ended_at, :created_at)");
        $stmt->execute([
            ':id' => $job['id'],
            ':asset_id' => $job['asset_id'],
            ':scan_type' => $job['scan_type'],
            ':status' => $job['status'],
            ':results' => $job['results'],
            ':error' => $job['error'] ?? null,
            ':started_at' => $job['started_at'] ?? null,
            ':ended_at' => $job['ended_at'] ?? null,
            ':created_at' => $job['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function getJobById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM scan_jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        return $job ?: null;
    }

    public function updateStatus(string $id, string $status): void
    {
        if ($status === 'running') {
            $stmt = $this->db->prepare("UPDATE scan_jobs SET status = ?, started_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            return;
        }

        $stmt = $this->db->prepare("UPDATE scan_jobs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function completeJob(string $id, int $results, string $status = 'completed'): void
    {
        $stmt = $this->db->prepare("UPDATE scan_jobs SET status = ?, results = ?, ended_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $results, $id]);
    }

    public function failJob(string $id, string $error): void
    {
        $stmt = $this->db->prepare("UPDATE scan_jobs SET status='failed', error = ?, ended_at = NOW() WHERE id = ?");
        $stmt->execute([$error, $id]);
    }

    public function getByAsset(string $assetId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM scan_jobs WHERE asset_id = ? ORDER BY created_at DESC");
        $stmt->execute([$assetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteScanJobsByAssetAndType(string $assetId, string $scanType): void
    {
        $stmt = $this->db->prepare("DELETE FROM scan_jobs WHERE asset_id = ? AND scan_type = ?");
        $stmt->execute([$assetId, $scanType]);
    }

    public function saveSubdomain(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO subdomains (id, asset_id, scan_job_id, name, source, is_active) VALUES (:id,:asset_id,:scan_job_id,:name,:source,:is_active)");
        $stmt->execute($data);
    }

    public function deleteSubdomainsByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM subdomains WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function deleteWHOISByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM whois_records WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function getSubdomainsByAsset(string $assetId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM subdomains WHERE asset_id = ? ORDER BY name");
        $stmt->execute([$assetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveWHOIS(string $scanId, string $assetId, array $record): void
    {
        $stmt = $this->db->prepare("INSERT INTO whois_records (id, asset_id, scan_job_id, registrar, created_date, expiry_date, raw_data) VALUES (UUID(), :asset_id, :scan_job_id, :registrar, :created_date, :expiry_date, :raw_data)");
        $stmt->execute([
            ':asset_id' => $assetId,
            ':scan_job_id' => $scanId,
            ':registrar' => $record['registrar'],
            ':created_date' => $record['created_date'],
            ':expiry_date' => $record['expiry_date'],
            ':raw_data' => $record['raw_data']
        ]);
    }

    public function getWHOISByAsset(string $assetId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM whois_records WHERE asset_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$assetId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? [$row] : [];
    }

    public function deleteDNSByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM dns_records WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function deleteIPResultsByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM ip_scan_results WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function deletePortResultsByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM port_scan_results WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function deleteSSLResultsByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM ssl_scan_results WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function deleteTechResultsByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM tech_scan_results WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function deleteCertTransResultsByAsset(string $assetId): void
    {
        $stmt = $this->db->prepare("DELETE FROM cert_trans_results WHERE asset_id = ?");
        $stmt->execute([$assetId]);
    }

    public function saveDNSRecord(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO dns_records (id, asset_id, scan_job_id, record_type, name, value, ttl) VALUES (UUID(), :asset_id, :scan_job_id, :record_type, :name, :value, :ttl)");
        $stmt->execute([
            ':asset_id' => $data['asset_id'],
            ':scan_job_id' => $data['scan_job_id'],
            ':record_type' => $data['record_type'],
            ':name' => $data['name'],
            ':value' => $data['value'],
            ':ttl' => $data['ttl']
        ]);
    }

    public function getDNSRecordsByAsset(string $assetId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM dns_records WHERE asset_id = ? ORDER BY record_type, name");
        $stmt->execute([$assetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveIPResult(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO ip_scan_results (id, scan_job_id, asset_id, ip_address, geolocation, asn, reverse_dns, created_at) VALUES (:id, :scan_job_id, :asset_id, :ip_address, :geolocation, :asn, :reverse_dns, :created_at)");
        $stmt->execute([
            ':id' => $data['id'],
            ':scan_job_id' => $data['scan_job_id'],
            ':asset_id' => $data['asset_id'],
            ':ip_address' => $data['ip_address'],
            ':geolocation' => json_encode($data['geolocation']),
            ':asn' => json_encode($data['asn']),
            ':reverse_dns' => $data['reverse_dns'],
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function getIPResultsByJob(string $scanId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM ip_scan_results WHERE scan_job_id = ?");
        $stmt->execute([$scanId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['geolocation'] = json_decode($row['geolocation'], true);
            $row['asn'] = json_decode($row['asn'], true);
        }
        return $rows;
    }

    public function savePortResult(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO port_scan_results (id, scan_job_id, asset_id, ip_address, open_ports, closed_ports, total_scanned, scan_duration_ms, created_at) VALUES (:id, :scan_job_id, :asset_id, :ip_address, :open_ports, :closed_ports, :total_scanned, :scan_duration_ms, :created_at)");
        $stmt->execute([
            ':id' => $data['id'],
            ':scan_job_id' => $data['scan_job_id'],
            ':asset_id' => $data['asset_id'],
            ':ip_address' => $data['ip_address'],
            ':open_ports' => json_encode($data['open_ports']),
            ':closed_ports' => $data['closed_ports'],
            ':total_scanned' => $data['total_scanned'],
            ':scan_duration_ms' => $data['scan_duration_ms'],
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function getPortResultsByJob(string $scanId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM port_scan_results WHERE scan_job_id = ?");
        $stmt->execute([$scanId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['open_ports'] = json_decode($row['open_ports'], true);
        }
        return $rows;
    }

    public function saveSSLResult(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO ssl_scan_results (id, scan_job_id, asset_id, domain, certificate, connection, grade, issues, created_at) VALUES (:id, :scan_job_id, :asset_id, :domain, :certificate, :connection, :grade, :issues, :created_at)");
        $stmt->execute([
            ':id' => $data['id'],
            ':scan_job_id' => $data['scan_job_id'],
            ':asset_id' => $data['asset_id'],
            ':domain' => $data['domain'],
            ':certificate' => json_encode($data['certificate']),
            ':connection' => json_encode($data['connection']),
            ':grade' => $data['grade'],
            ':issues' => json_encode($data['issues']),
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function getSSLResultsByJob(string $scanId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM ssl_scan_results WHERE scan_job_id = ?");
        $stmt->execute([$scanId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['certificate'] = json_decode($row['certificate'], true);
            $row['connection'] = json_decode($row['connection'], true);
            $row['issues'] = json_decode($row['issues'], true);
        }
        return $rows;
    }

    public function saveTechResult(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO tech_scan_results (id, scan_job_id, asset_id, domain, technologies, headers, meta_tags, created_at) VALUES (:id, :scan_job_id, :asset_id, :domain, :technologies, :headers, :meta_tags, :created_at)");
        $stmt->execute([
            ':id' => $data['id'],
            ':scan_job_id' => $data['scan_job_id'],
            ':asset_id' => $data['asset_id'],
            ':domain' => $data['domain'],
            ':technologies' => json_encode($data['technologies']),
            ':headers' => json_encode($data['headers']),
            ':meta_tags' => json_encode($data['meta_tags']),
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function getTechResultsByJob(string $scanId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tech_scan_results WHERE scan_job_id = ?");
        $stmt->execute([$scanId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['technologies'] = json_decode($row['technologies'], true);
            $row['headers'] = json_decode($row['headers'], true);
            $row['meta_tags'] = json_decode($row['meta_tags'], true);
        }
        return $rows;
    }

    public function saveCertTransResult(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO cert_trans_results (id, scan_job_id, asset_id, domain, certificate, created_at) VALUES (:id, :scan_job_id, :asset_id, :domain, :certificate, :created_at)");
        $stmt->execute([
            ':id' => $data['id'],
            ':scan_job_id' => $data['scan_job_id'],
            ':asset_id' => $data['asset_id'],
            ':domain' => $data['domain'],
            ':certificate' => json_encode($data['certificate']),
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function getCertTransResultsByJob(string $scanId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM cert_trans_results WHERE scan_job_id = ?");
        $stmt->execute([$scanId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['certificate'] = json_decode($row['certificate'], true);
        }
        return $rows;
    }

    public function getResults(string $scanId): array
    {
        $job = $this->getJobById($scanId);
        if (!$job) {
            return [];
        }

        switch ($job['scan_type']) {
            case 'subdomain':
                $stmt = $this->db->prepare("SELECT * FROM subdomains WHERE scan_job_id = ?");
                $stmt->execute([$scanId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            case 'whois':
                $stmt = $this->db->prepare("SELECT * FROM whois_records WHERE scan_job_id = ?");
                $stmt->execute([$scanId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            case 'dns':
                $stmt = $this->db->prepare("SELECT * FROM dns_records WHERE scan_job_id = ?");
                $stmt->execute([$scanId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            case 'ip':
                return $this->getIPResultsByJob($scanId);
            case 'port':
                return $this->getPortResultsByJob($scanId);
            case 'ssl':
                return $this->getSSLResultsByJob($scanId);
            case 'tech':
                return $this->getTechResultsByJob($scanId);
            case 'cert_trans':
                return $this->getCertTransResultsByJob($scanId);
            default:
                return [];
        }
    }
}
