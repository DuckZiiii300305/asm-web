<?php

require_once __DIR__ . '/env.php';

loadEnv(__DIR__ . '/../../.env.docker');

return [
    "db_host" => $_ENV["DB_HOST"] ?? "127.0.0.1",
    "db_port" => $_ENV["DB_PORT"] ?? "3306",
    "db_name" => $_ENV["DB_NAME"] ?? "asm_web",
    "db_user" => $_ENV["DB_USER"] ?? "root",
    "db_pass" => $_ENV["DB_PASS"] ?? ""
];