<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$db = $pdo->query("SELECT DATABASE()")->fetchColumn();

// limpa “fantasmas” (se o objeto para de mandar)
$pdo->exec("DELETE FROM sl_online WHERE last_seen < (NOW() - INTERVAL 45 SECOND)");

$rows = $pdo->query("
  SELECT
    avatar_uuid,
    avatar_name,
    avatar_image,
    region,
    parcel,
    slurl,
    first_seen,
    last_seen,
    TIMESTAMPDIFF(MINUTE, first_seen, NOW()) AS online_minutes
  FROM sl_online
  ORDER BY last_seen DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  "db" => $db,
  "count" => count($rows),
  "rows" => $rows
]);
