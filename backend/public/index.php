<?php

// CORS middleware (Bài 3.1)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../internal/bootstrap.php';

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = rtrim($scriptDir, '/');

if ($scriptDir !== '' && strpos($uri, $scriptDir) === 0) {
    $uri = substr($uri, strlen($scriptDir));
}

$uri = '/' . trim($uri, '/');

try {
    $db = getDB();
} catch (Exception $e) {
    Response::error("Database unavailable", 503);
    exit;
}

$repo = new AssetMySQLRepository($db);
$service = new AssetService($repo);
$scanRepo = new ScanMySQLRepository($db);
$scanService = new ScanService($repo, $scanRepo);
$scanHandler = new ScanHandler($scanService);
$assetHandler = new AssetHandler($service);
$healthHandler = new HealthHandler();

$router = new Router();

$router->get("/", function () {
    Response::json([
        "message" => "ASM Web API running"
    ]);
});

$router->get("/health", [$healthHandler, "health"]);

$router->get("/assets/stats", [$assetHandler, "stats"]);
$router->get("/assets/count", [$assetHandler, "count"]);
$router->post("/assets/batch", [$assetHandler, "batchCreate"]);
$router->delete("/assets/batch", [$assetHandler, "batchDelete"]);
$router->get("/assets/search", [$assetHandler, "search"]);

$router->get("/assets", [$assetHandler, "list"]);
$router->post("/assets", [$assetHandler, "create"]);

$router->get("/assets/{id}", [$assetHandler, "get"]);
$router->put("/assets/{id}", [$assetHandler, "update"]);
$router->delete("/assets/{id}", [$assetHandler, "delete"]);

$router->get("/scan-jobs/{id}", [$scanHandler, "status"]);
$router->post("/assets/{id}/scan", [$scanHandler, "start"]);
$router->get("/assets/{id}/scans", [$scanHandler, "listByAsset"]);
$router->get("/scan-jobs/{id}/results", [$scanHandler, "getResults"]);
$router->get("/assets/{id}/dns", [$scanHandler, "assetDNS"]);
$router->get("/assets/{id}/whois", [$scanHandler, "assetWHOIS"]);
$router->get("/assets/{id}/subdomains", [$scanHandler, "assetSubdomains"]);
$router->get("/assets/{id}/results", [$scanHandler, "assetResults"]);

$router->dispatch($_SERVER["REQUEST_METHOD"], $uri);
