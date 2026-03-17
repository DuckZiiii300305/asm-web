<?php

class AssetHandler
{
    private $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function create()
    {
        try {

            $data = json_decode(file_get_contents("php://input"), true);

            $asset = $this->service->create($data);

            Response::json($asset, 201);

        } catch (DomainError $e) {

            Response::error($e->getMessage(), $e->getCode());

        } catch (Exception $e) {

            Response::error("internal server error", 500);
        }
    }

    public function list()
    {
        $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
        $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 20;

        if ($limit > 100) {
            $limit = 100;
        }

        if ($page < 1) {
            $page = 1;
        }

        $type = $_GET["type"] ?? null;
        $status = $_GET["status"] ?? null;
        $search = $_GET["search"] ?? null;

        $sortBy = $_GET["sort_by"] ?? "created_at";
        $sortOrder = $_GET["sort_order"] ?? "desc";

        $result = $this->service->list(
            $page,
            $limit,
            $type,
            $status,
            $search,
            $sortBy,
            $sortOrder
        );
        Response::json($result);
    }

    public function get($id)
    {
        try {

            Response::json($this->service->get($id));

        } catch (DomainError $e) {

            Response::error($e->getMessage(), $e->getCode());

        } catch (Exception $e) {

            Response::error("internal server error", 500);
        }
    }

    public function update($id)
    {
        try {

            $data = json_decode(file_get_contents("php://input"), true);

            $asset = $this->service->update($id, $data);

            Response::json($asset);

        } catch (DomainError $e) {

            Response::error($e->getMessage(), $e->getCode());

        } catch (Exception $e) {

            Response::error("internal server error", 500);
        }
    }

    public function delete($id)
    {
        try {

            $this->service->delete($id);

            Response::json(["deleted" => true]);

        } catch (DomainError $e) {

            Response::error($e->getMessage(), $e->getCode());

        } catch (Exception $e) {

            Response::error("internal server error", 500);
        }
    }
    public function stats()
    {
        try {
            $stats = $this->service->getStats();

            Response::json($stats->toArray());
        } catch (Exception $e) {
            Response::error(500, $e->getMessage());
        }
    }
    public function count()
    {
        try {

            $type = $_GET["type"] ?? null;
            $status = $_GET["status"] ?? null;

            $count = $this->service->countAssets($type, $status);

            Response::json([
                "count" => $count,
                "filters" => [
                    "type" => $type,
                    "status" => $status
                ]
            ]);

        } catch (Exception $e) {
            Response::error(400, $e->getMessage());
        }
    }
    public function batchCreate()
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $result = $this->service->batchCreate($input);
            Response::json($result, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function batchDelete()
    {
        try {
            if (!isset($_GET["ids"])) {
                Response::error("ids required", 400);
                return;
            }

            $ids = explode(",", $_GET["ids"]);
            $result = $this->service->batchDelete($ids);
            Response::json($result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function search()
    {
        if (!isset($_GET['q'])) {
            Response::error("Missing query parameter q", 400);
            return;
        }

        $query = $_GET['q'];

        try {
            $results = $this->service->search($query);
            Response::json($results);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}