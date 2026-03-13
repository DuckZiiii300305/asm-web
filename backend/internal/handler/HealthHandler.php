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

            // total connections
            $openConnections = $db
                ->query("SELECT COUNT(*) FROM information_schema.PROCESSLIST")
                ->fetchColumn();

            // active connections
            $inUse = $db
                ->query("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE Command != 'Sleep'")
                ->fetchColumn();

            // max connections
            $stmt = $db->query("SHOW VARIABLES LIKE 'max_connections'");
            $max = $stmt->fetch(PDO::FETCH_ASSOC)["Value"];

            $idle = $openConnections - $inUse;

            $response = [
                "status" => "ok",
                "database" => [
                    "status" => "connected",
                    "open_connections" => (int)$openConnections,
                    "in_use" => (int)$inUse,
                    "idle" => (int)$idle,
                    "max_open" => (int)$max
                ],
                "timestamp" => gmdate("c")
            ];

            http_response_code(200);

        } catch (Exception $e) {

            error_log("Health check failed: " . $e->getMessage());
            $response = [
                "status" => "degraded",
                "database" => [
                    "status" => "disconnected"
                ],
                "timestamp" => gmdate("c")
            ];

            http_response_code(503);
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}