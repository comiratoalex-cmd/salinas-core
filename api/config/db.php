<?php
$host = "db5019378081.hosting-data.io";
$db   = "dbs15162566";
$user = "dbu2347265";
$pass = "Sunlight2024!Go";
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro DB: " . $e->getMessage());
}
