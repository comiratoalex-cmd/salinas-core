<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$limit = isset($_GET["limit"]) ? max(10, min(500, (int)$_GET["limit"])) : 200;
$online_only = isset($_GET["online"]) && $_GET["online"] === "1";

$sql = "
  SELECT
    id,
    avatar_uuid,
    avatar_name,
    region,
    parcel,
    slurl,
    entry_time,
    exit_time,
    duration_minutes,
    event_time
  FROM sl_sessions
";

if ($online_only) $sql .= " WHERE exit_time IS NULL ";

$sql .= " ORDER BY COALESCE(entry_time, event_time) DESC LIMIT :lim";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll();

// monta avatar_image sem depender de coluna no banco
foreach ($rows as &$r) {
  $r["avatar_image"] = "https://world.secondlife.com/resident/" . $r["avatar_uuid"] . "/image";
}

echo json_encode($rows);
