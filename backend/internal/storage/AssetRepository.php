<?php

interface AssetRepository
{
    public function create($asset);
    public function getAll();
    public function getById($id);
    public function update($id, $asset);
    public function delete($id);
    public function stats();
    public function count($type = null, $status = null);
    public function batchCreate($assets);
    public function list($page, $limit, $type = null, $status = null);
    public function search(string $query): array;
}