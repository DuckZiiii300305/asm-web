<?php

require_once __DIR__ . '/../internal/config/database.php';
require_once __DIR__ . '/../internal/router/Router.php';

require_once __DIR__ . '/../internal/storage/mysql/AssetMySQLRepository.php';
require_once __DIR__ . '/../internal/service/AssetService.php';

require_once __DIR__ . '/../internal/handler/AssetHandler.php';
require_once __DIR__ . '/../internal/handler/HealthHandler.php';
require_once __DIR__ . '/../internal/handler/Response.php';
require_once __DIR__ . '/../internal/config/env.php';

loadEnv(__DIR__ . '/../.env');

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = str_replace("/asm-web/backend/public", "", $uri);

/* ---- health check chạy trước DB ---- */
if ($uri === "/health") {
    $healthHandler = new HealthHandler();
    $healthHandler->health();
    exit;
}

/* ---- connect DB cho các API khác ---- */
try {

    $db = getDB();

} catch (Exception $e) {

    http_response_code(503);

    echo json_encode([
        "error" => "Database unavailable"
    ]);

    exit;
}

$repo = new AssetMySQLRepository($db);
$service = new AssetService($repo);

$assetHandler = new AssetHandler($service);
$healthHandler = new HealthHandler();

$router = new Router();


// ROOT
$router->get("/", function () {
    Response::json([
        "message" => "ASM Web API running"
    ]);
});

// HEALTH
$router->get("/health", [$healthHandler, "health"]);


// ----- STATIC ROUTES
$router->get("/assets/stats", [$assetHandler, "stats"]);
$router->get("/assets/count", [$assetHandler, "count"]);
$router->post("/assets/batch", [$assetHandler, "batchCreate"]);
$router->delete("/assets/batch", [$assetHandler, "batchDelete"]);
$router->get('/assets/search', [$assetHandler, 'search']);


// ----- COLLECTION ROUTES
$router->get("/assets", [$assetHandler, "list"]);
$router->post("/assets", [$assetHandler, "create"]);


// ----- PARAM ROUTES
$router->get("/assets/{id}", [$assetHandler, "get"]);
$router->put("/assets/{id}", [$assetHandler, "update"]);
$router->delete("/assets/{id}", [$assetHandler, "delete"]);


$router->dispatch($_SERVER["REQUEST_METHOD"], $uri);