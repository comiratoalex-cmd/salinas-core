<?php
$host = "db5019378081.hosting-data.io";
$db   = "dbs15162566";
$user = "dbu2347265";
$pass = "SUA_SENHA_REAL_AQUI";
$port = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    echo "✅ CONEXÃO OK";
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
