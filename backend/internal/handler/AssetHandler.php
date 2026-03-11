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

        $result = $this->service->list($page, $limit, $type, $status);

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

            http_response_code(201);

            echo json_encode($result);

        } catch (Exception $e) {

            http_response_code(400);

            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }
    public function batchDelete()
    {
        try {

            if (!isset($_GET["ids"])) {
                Response::json([
                    "error" => "ids required"
                ], 400);
                return;
            }

            $ids = explode(",", $_GET["ids"]);

            $result = $this->service->batchDelete($ids);

            Response::json($result);

        } catch (Exception $e) {

            Response::json([
                "error" => $e->getMessage()
            ], 400);

        }
    }
    public function search()
    {
        if (!isset($_GET['q'])) {
            http_response_code(400);
            echo json_encode([
                "error" => "Missing query parameter q"
            ]);
            return;
        }

        $query = $_GET['q'];

        try {
            $results = $this->service->search($query);

            echo json_encode($results);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }
}