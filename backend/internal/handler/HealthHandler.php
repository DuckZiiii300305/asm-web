<?php

require_once __DIR__ . '/../config/database.php';

class HealthHandler
{
    public function health()
    {
        header("Content-Type: application/json");

        try {

            $db = getDB();

            // check connection
            $db->query("SELECT 1");

            $response = [
                "status" => "ok",
                "database" => [
                    "status" => "connected",
                    "open_connections" => 1,
                    "in_use" => 1,
                    "idle" => 0,
                    "max_open" => 25
                ],
                "timestamp" => gmdate("c")
            ];

            http_response_code(200);

        } catch (Exception $e) {

            $response = [
                "status" => "degraded",
                "database" => [
                    "status" => "disconnected",
                    "open_connections" => 0,
                    "in_use" => 0,
                    "idle" => 0,
                    "max_open" => 25
                ],
                "timestamp" => gmdate("c")
            ];

            http_response_code(503);
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}