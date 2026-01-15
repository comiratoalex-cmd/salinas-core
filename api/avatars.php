<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$limit = isset($_GET["limit"]) ? max(10, min(500, (int)$_GET["limit"])) : 200;

$sql = "
  SELECT
    avatar_uuid,
    MAX(avatar_name) AS avatar_name,
    COUNT(*) AS sessions_count,
    COALESCE(SUM(duration_minutes),0) AS total_minutes,
    MAX(COALESCE(exit_time, entry_time, event_time)) AS last_seen,
    SUM(CASE WHEN exit_time IS NULL THEN 1 ELSE 0 END) AS online_sessions
  FROM sl_sessions
  GROUP BY avatar_uuid
  ORDER BY last_seen DESC
  LIMIT :lim
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
  $r["avatar_image"] = "https://world.secondlife.com/resident/" . $r["avatar_uuid"] . "/image";
  $r["is_online"] = ((int)$r["online_sessions"] > 0);
}

echo json_encode($rows);
