<?php

require_once __DIR__ . '/../AssetRepository.php';
require_once __DIR__ . '/../../model/Stats.php';
class AssetMySQLRepository implements AssetRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($asset)
    {
        $sql = "INSERT INTO assets (id,name,type,status,created_at,updated_at)
                VALUES (:id,:name,:type,:status,:created,:updated)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ":id" => $asset->id,
            ":name" => $asset->name,
            ":type" => $asset->type,
            ":status" => $asset->status,
            ":created" => $asset->created_at,
            ":updated" => $asset->updated_at
        ]);
    }

    public function getAll()
    {
        return $this->db->query("SELECT * FROM assets")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE id=?");
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $asset)
    {
        $sql = "UPDATE assets 
                SET name=?, type=?, status=?, updated_at=? 
                WHERE id=?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $asset->name,
            $asset->type,
            $asset->status,
            $asset->updated_at,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM assets WHERE id=?");
        return $stmt->execute([$id]);
    }
    public function stats()
    {
        $totalStmt = $this->db->query("SELECT COUNT(*) as total FROM assets");
        $total = $totalStmt->fetch()["total"];

        $typeStmt = $this->db->query("
            SELECT type, COUNT(*) as count
            FROM assets
            GROUP BY type
        ");

        $byType = [];
        while ($row = $typeStmt->fetch()) {
            $byType[$row["type"]] = (int)$row["count"];
        }

        $statusStmt = $this->db->query("
            SELECT status, COUNT(*) as count
            FROM assets
            GROUP BY status
        ");

        $byStatus = [];
        while ($row = $statusStmt->fetch()) {
            $byStatus[$row["status"]] = (int)$row["count"];
        }

        return new AssetStats($total, $byType, $byStatus);
    }

    public function count($type = null, $status = null)
    {
        $sql = "SELECT COUNT(*) as count FROM assets WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetch()["count"];
    }
    public function batchCreate($assets)
    {
        $ids = [];

        try {

            $this->db->beginTransaction();

            foreach ($assets as $asset) {

                $sql = "INSERT INTO assets (id, name, type, status, created_at, updated_at)
                        VALUES (:id, :name, :type, :status, :created_at, :updated_at)";

                $stmt = $this->db->prepare($sql);

                $stmt->execute([
                    ":id" => $asset->id,
                    ":name" => $asset->name,
                    ":type" => $asset->type,
                    ":status" => $asset->status,
                    ":created_at" => $asset->created_at,
                    ":updated_at" => $asset->updated_at
                ]);

                $ids[] = $asset->id;
            }

            $this->db->commit();

            return [
                "created" => count($ids),
                "ids" => $ids
            ];

        } catch (Exception $e) {

            $this->db->rollBack();

            throw $e;
        }
    }
    public function list($page, $limit, $type = null, $status = null)
    {
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($type) {
            $where[] = "type = ?";
            $params[] = $type;
        }

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereSQL = "";

        if (!empty($where)) {
            $whereSQL = "WHERE " . implode(" AND ", $where);
        }

        $sql = "
            SELECT * FROM assets
            $whereSQL
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        $index = 1;

        foreach ($params as $param) {
            $stmt->bindValue($index++, $param);
        }

        $stmt->bindValue($index++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);

        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSQL = "
            SELECT COUNT(*) as total
            FROM assets
            $whereSQL
        ";

        $stmt = $this->db->prepare($countSQL);
        $stmt->execute($params);

        $total = $stmt->fetch()["total"];

        $totalPages = ceil($total / $limit);

        return [
            "data" => $data,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "total" => (int)$total,
                "total_pages" => (int)$totalPages
            ]
        ];
    }
    public function search(string $query): array
    {
        $sql = "
            SELECT id, name, type, created_at
            FROM assets
            WHERE name LIKE ?
            LIMIT 100
        ";

        $stmt = $this->db->prepare($sql);

        $pattern = '%' . $query . '%';

        $stmt->execute([$pattern]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    }