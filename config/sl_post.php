<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== DB CONFIG ======
$host = "db5019378081.hosting-data.io";
$db   = "dbs15162566";
$user = "dbu2347265";
$pass = "Sunlight2024!Go";

try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["status"=>"db_error","message"=>$e->getMessage()]);
  exit;
}

$raw = file_get_contents("php://input");
if (!$raw) { http_response_code(400); echo json_encode(["status"=>"no_body"]); exit; }

$data = json_decode($raw, true);
if (!is_array($data)) { http_response_code(400); echo json_encode(["status"=>"invalid_json","raw"=>$raw]); exit; }

// required
$req = ["avatar_uuid","avatar_name","event_type","region","parcel","slurl","timestamp"];
foreach ($req as $k) {
  if (!isset($data[$k]) || trim((string)$data[$k])==="") {
    http_response_code(422);
    echo json_encode(["status"=>"missing_$k"]);
    exit;
  }
}

$uuid   = trim($data["avatar_uuid"]);
$name   = trim($data["avatar_name"]);
$type   = strtolower(trim($data["event_type"]));
$region = trim($data["region"]);
$parcel = trim($data["parcel"]);
$slurl  = trim($data["slurl"]);
$ts     = trim($data["timestamp"]); // "YYYY-MM-DD HH:MM:SS"

if ($type !== "entry" && $type !== "exit") {
  http_response_code(422);
  echo json_encode(["status"=>"invalid_event_type","got"=>$type]);
  exit;
}

try {
  if ($type === "entry") {
    // grava entrada (abre sessão)
    $stmt = $pdo->prepare("
      INSERT INTO sl_sessions
      (avatar_uuid, avatar_name, event_type, region, parcel, slurl, entry_time, event_time)
      VALUES
      (:uuid, :name, 'entry', :region, :parcel, :slurl, :t, :t)
    ");
    $stmt->execute([
      ":uuid"=>$uuid, ":name"=>$name, ":region"=>$region, ":parcel"=>$parcel, ":slurl"=>$slurl, ":t"=>$ts
    ]);
    echo json_encode(["status"=>"ok","logged"=>"entry"]);
    exit;
  }

  // EXIT: fecha a última sessão aberta desse avatar
  $stmt = $pdo->prepare("
    UPDATE sl_sessions
    SET
      event_type='exit',
      exit_time = :t,
      event_time = :t,
      duration_minutes = TIMESTAMPDIFF(MINUTE, entry_time, :t)
    WHERE avatar_uuid = :uuid
      AND exit_time IS NULL
    ORDER BY entry_time DESC
    LIMIT 1
  ");
  $stmt->execute([":uuid"=>$uuid, ":t"=>$ts]);

  if ($stmt->rowCount() === 0) {
    // não tinha sessão aberta (evita “exit solto”)
    echo json_encode(["status"=>"no_open_session"]);
    exit;
  }

  echo json_encode(["status"=>"ok","logged"=>"exit"]);
  exit;

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["status"=>"insert_error","message"=>$e->getMessage()]);
  exit;
}
