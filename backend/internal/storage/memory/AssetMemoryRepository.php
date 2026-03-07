<?php

require_once __DIR__ . '/../AssetRepository.php';

class AssetMemoryRepository implements AssetRepository
{
    private $assets = [];

    public function create($asset)
    {
        $this->assets[$asset->id] = $asset;
        return $asset;
    }

    public function getAll()
    {
        return array_values($this->assets);
    }

    public function getById($id)
    {
        return $this->assets[$id] ?? null;
    }

    public function update($id, $asset)
    {
        $this->assets[$id] = $asset;
        return $asset;
    }

    public function delete($id)
    {
        unset($this->assets[$id]);
    }
}