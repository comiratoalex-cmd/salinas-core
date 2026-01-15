<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php"; // precisa fornecer $pdo (PDO)

$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 200;
if ($limit < 1) $limit = 1;
if ($limit > 500) $limit = 500;

$activeOnly = isset($_GET["active"]) ? (int)$_GET["active"] : 1;

$sql = "
  SELECT id, avatar_uuid, avatar_name, reason, added_by, added_at, active
  FROM sl_bans
  " . ($activeOnly ? "WHERE active=1" : "") . "
  ORDER BY added_at DESC
  LIMIT :lim
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// adiciona avatar_image sempre
foreach ($rows as &$r) {
  $r["avatar_image"] = "https://world.secondlife.com/resident/".$r["avatar_uuid"]."/image";
}

echo json_encode($rows);