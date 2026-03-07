<?php

function connectWithRetry($maxRetries = 5)
{
    $host = "127.0.0.1";
    $db   = "asm_web";
    $user = "root";
    $pass = "";
    $charset = "utf8mb4";

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $attempt = 1;

    while ($attempt <= $maxRetries) {

        error_log("🔄 Database connection attempt {$attempt}/{$maxRetries}...");

        try {

            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);

            error_log("✅ Database connected successfully!");

            return $pdo;

        } catch (PDOException $e) {

            if ($attempt == $maxRetries) {
                error_log("❌ Database connection failed after {$maxRetries} attempts.");
                throw $e;
            }

            $wait = pow(2, $attempt - 1);

            error_log("⚠️ Connection failed: {$e->getMessage()} - retry in {$wait}s");

            sleep($wait);

            $attempt++;
        }
    }
}

function getDB()
{
    static $db = null;

    if ($db === null) {
        $db = connectWithRetry(5);
    }

    return $db;
}


function checkDBConnection()
{
    $host = "127.0.0.1";
    $db   = "asm_web";
    $user = "root";
    $pass = "";
    $charset = "utf8mb4";

    try {

        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=$charset",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 1
            ]
        );

        $pdo->query("SELECT 1");

        return true;

    } catch (Exception $e) {
        return false;
    }
}