<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$db = $pdo->query("SELECT DATABASE()")->fetchColumn();

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$countOnline = 0;
$countSessions = 0;

try { $countOnline = (int)$pdo->query("SELECT COUNT(*) FROM sl_online")->fetchColumn(); } catch(Exception $e) {}
try { $countSessions = (int)$pdo->query("SELECT COUNT(*) FROM sl_sessions")->fetchColumn(); } catch(Exception $e) {}

echo json_encode([
  "db" => $db,
  "has_sl_online" => in_array("sl_online", $tables),
  "has_sl_sessions" => in_array("sl_sessions", $tables),
  "sl_online_rows" => $countOnline,
  "sl_sessions_rows" => $countSessions,
  "server_time" => $pdo->query("SELECT NOW()")->fetchColumn()
]);
