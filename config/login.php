<?php
require "teste_conexao.php";

$stmt = $pdo->query("SELECT id, username, password_hash FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
var_dump($users);