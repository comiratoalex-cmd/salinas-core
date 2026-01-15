<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$total_sessions = (int)$pdo->query("SELECT COUNT(*) AS c FROM sl_sessions")->fetchColumn();
$unique_avatars = (int)$pdo->query("SELECT COUNT(DISTINCT avatar_uuid) FROM sl_sessions")->fetchColumn();
$total_minutes  = (int)$pdo->query("SELECT COALESCE(SUM(duration_minutes),0) FROM sl_sessions")->fetchColumn();
$online_now     = (int)$pdo->query("SELECT COUNT(*) FROM sl_sessions WHERE exit_time IS NULL")->fetchColumn();

echo json_encode([
  "total_sessions" => $total_sessions,
  "unique_avatars" => $unique_avatars,
  "total_minutes"  => $total_minutes,
  "online_now"     => $online_now
]);
