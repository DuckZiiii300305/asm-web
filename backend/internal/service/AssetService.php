<?php

require_once __DIR__.'/../model/Asset.php';
require_once __DIR__.'/../model/Errors.php';

class AssetService
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    private function uuid()
    {
        return bin2hex(random_bytes(16));
    }

    public function create($data)
    {
        if (!$data) {
            throw Errors::InvalidInput();
        }

        if (empty($data["name"])) {
            throw Errors::EmptyName();
        }

        if (!isset($data["type"]) || !AssetType::isValid($data["type"])) {
            throw Errors::InvalidType();
        }

        if (!AssetValidator::validateNameByType($data["name"], $data["type"])) {
            throw Errors::InvalidInput();
        }

        $status = $data["status"] ?? AssetStatus::ACTIVE;

        if (!AssetStatus::isValid($status)) {
            throw Errors::InvalidStatus();
        }

        $asset = new Asset();

        $asset->id = $this->uuid();
        $asset->name = $data["name"];
        $asset->type = $data["type"];
        $asset->status = $status;

        $asset->created_at = date("Y-m-d H:i:s");
        $asset->updated_at = date("Y-m-d H:i:s");

        $this->repo->create($asset);

        return $asset;
    }

    public function list(
        $page = 1,
        $limit = 20,
        $type = null,
        $status = null,
        $search = null,
        $sortBy = "created_at",
        $sortOrder = "desc"
    )
    {
        if ($type && !AssetType::isValid($type)) {
            throw Errors::InvalidType();
        }

        if ($status && !AssetStatus::isValid($status)) {
            throw Errors::InvalidStatus();
        }

        $allowedSort = ["name","type","status","created_at","updated_at"];

        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = "created_at";
        }

        $sortOrder = strtolower($sortOrder) === "asc" ? "asc" : "desc";

        return $this->repo->list(
            $page,
            $limit,
            $type,
            $status,
            $search,
            $sortBy,
            $sortOrder
        );
    }

    public function get($id)
    {
        $asset = $this->repo->getById($id);

        if (!$asset) {
            throw Errors::NotFound();
        }

        return $asset;
    }

    public function update($id, $data)
    {
        $asset = $this->repo->getById($id);

        if (!$asset) {
            throw Errors::NotFound();
        }

        if (isset($data["name"]) && $data["name"] === "") {
            throw Errors::EmptyName();
        }

        if (isset($data["type"]) && !AssetType::isValid($data["type"])) {
            throw Errors::InvalidType();
        }

        if (isset($data["status"]) && !AssetStatus::isValid($data["status"])) {
            throw Errors::InvalidStatus();
        }

        $asset["name"] = $data["name"] ?? $asset["name"];
        $asset["type"] = $data["type"] ?? $asset["type"];
        $asset["status"] = $data["status"] ?? $asset["status"];
        $asset["updated_at"] = date("Y-m-d H:i:s");

        $this->repo->update($id, (object)$asset);

        return $asset;
    }

    public function delete($id)
    {
        $asset = $this->repo->getById($id);

        if (!$asset) {
            throw Errors::NotFound();
        }

        $this->repo->delete($id);

        return true;
    }
    public function getStats()
    {
        return $this->repo->stats();
    }

    public function countAssets($type = null, $status = null)
    {
        if ($type && !AssetType::isValid($type)) {
            throw Errors::InvalidType();
        }

        if ($status && !AssetStatus::isValid($status)) {
            throw Errors::InvalidStatus();
        }

        return $this->repo->count($type, $status);
    }
    public function batchCreate($data)
    {
        if (!isset($data["assets"]) || !is_array($data["assets"])) {
            throw Errors::InvalidInput();
        }

        $assets = $data["assets"];

        if (count($assets) === 0) {
            throw new Exception("assets empty");
        }

        if (count($assets) > 100) {
            throw new Exception("max 100 assets per request");
        }

        $validated = [];

        foreach ($assets as $item) {

            if (empty($item["name"])) {
                throw Errors::EmptyName();
            }

            if (!isset($item["type"]) || !AssetType::isValid($item["type"])) {
                throw Errors::InvalidType();
            }

            $status = $item["status"] ?? AssetStatus::ACTIVE;

            if (!AssetStatus::isValid($status)) {
                throw Errors::InvalidStatus();
            }

            $asset = new Asset();

            $asset->id = $this->uuid();
            $asset->name = $item["name"];
            $asset->type = $item["type"];
            $asset->status = $status;
            $asset->created_at = date("Y-m-d H:i:s");
            $asset->updated_at = date("Y-m-d H:i:s");

            $validated[] = $asset;
        }

        return $this->repo->batchCreate($validated);
    }
    public function batchDelete($ids)
    {
        if (!is_array($ids) || count($ids) == 0) {
            throw new Exception("invalid ids");
        }

        $deleted = 0;
        $notFound = 0;

        foreach ($ids as $id) {

            $asset = $this->repo->getById($id);

            if (!$asset) {
                $notFound++;
                continue;
            }

            $this->repo->delete($id);
            $deleted++;
        }

        return [
            "deleted" => $deleted,
            "not_found" => $notFound
        ];
    }
    public function search(string $query): array
    {
        if (trim($query) === '') {
            throw new Exception("Search query cannot be empty");
        }

        return $this->repo->search($query);
    }
}