<?php

function connectWithRetry($maxRetries = 5)
{
    $host = $_ENV["DB_HOST"] ?? "127.0.0.1";
    $db   = $_ENV["DB_NAME"] ?? "asm_web";
    $user = $_ENV["DB_USER"] ?? "root";
    $pass = $_ENV["DB_PASS"] ?? "";
    $charset = $_ENV["DB_CHARSET"] ?? "utf8mb4";

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $attempt = 1;

    while ($attempt <= $maxRetries) {

        error_log("Database connection attempt {$attempt}/{$maxRetries}");

        try {

            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);

            return $pdo;

        } catch (PDOException $e) {

            if ($attempt == $maxRetries) {
                throw $e;
            }

            $wait = pow(2, $attempt - 1);

            error_log("Connection failed: {$e->getMessage()} retry in {$wait}s");

            sleep($wait);

            $attempt++;
        }
    }
}

function getDB()
{
    static $db = null;

    if ($db === null) {
        $db = connectWithRetry();
    }

    return $db;
}