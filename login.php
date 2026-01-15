<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit;
}

$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";

if (!$username || !$password) {
    die("Missing credentials");
}

$stmt = $pdo->prepare("
    SELECT id, password_hash
    FROM users
    WHERE username = :u OR email = :u
    LIMIT 1
");
$stmt->execute(["u" => $username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Invalid login");
}

if (hash("sha256", $password) !== $user["password_hash"]) {
    die("Invalid login");
}

session_start();
$_SESSION["user_id"] = $user["id"];

echo "LOGIN_OK";
