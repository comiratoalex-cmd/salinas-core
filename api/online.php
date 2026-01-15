<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ IMPORTANTE: esse caminho precisa existir
require_once __DIR__ . "/config/db.php";

$raw = file_get_contents("php://input");
if (!$raw) { http_response_code(400); echo json_encode(["status"=>"no_body"]); exit; }

$data = json_decode($raw, true);
if (!is_array($data)) { http_response_code(400); echo json_encode(["status"=>"invalid_json","raw"=>$raw]); exit; }

$region = trim((string)($data["region"] ?? ""));
$parcel = trim((string)($data["parcel"] ?? ""));
$slurl  = trim((string)($data["slurl"] ?? ""));
$ts     = trim((string)($data["timestamp"] ?? ""));

// ✅ aceita avatars como array OU como string JSON
$avatars = $data["avatars"] ?? [];
if (is_string($avatars)) {
  $decoded = json_decode($avatars, true);
  if (is_array($decoded)) $avatars = $decoded;
}

if ($region==="" || $parcel==="" || $slurl==="" || $ts==="" || !is_array($avatars)) {
  http_response_code(422);
  echo json_encode(["status"=>"missing_fields","region"=>$region,"parcel"=>$parcel,"slurl"=>$slurl,"timestamp"=>$ts,"avatars_type"=>gettype($avatars)]);
  exit;
}

$db = $pdo->query("SELECT DATABASE()")->fetchColumn();

// remove offline
$pdo->exec("DELETE FROM sl_online WHERE last_seen < (NOW() - INTERVAL 45 SECOND)");

$up = $pdo->prepare("
  INSERT INTO sl_online (avatar_uuid, avatar_name, avatar_image, region, parcel, slurl, first_seen, last_seen)
  VALUES (:uuid, :name, :img, :region, :parcel, :slurl, :t, :t)
  ON DUPLICATE KEY UPDATE
    avatar_name=VALUES(avatar_name),
    avatar_image=VALUES(avatar_image),
    region=VALUES(region),
    parcel=VALUES(parcel),
    slurl=VALUES(slurl),
    last_seen=VALUES(last_seen)
");

$count = 0;

foreach ($avatars as $a) {
  $uuid = trim((string)($a["uuid"] ?? ""));
  $name = trim((string)($a["name"] ?? ""));
  if ($uuid==="" || $name==="") continue;

  $img = trim((string)($a["image"] ?? ""));
  if ($img==="") $img = "https://world.secondlife.com/resident/$uuid/image";

  $up->execute([
    ":uuid"=>$uuid,
    ":name"=>mb_substr($name, 0, 100),
    ":img"=>mb_substr($img, 0, 255),
    ":region"=>mb_substr($region, 0, 100),
    ":parcel"=>mb_substr($parcel, 0, 100),
    ":slurl"=>mb_substr($slurl, 0, 255),
    ":t"=>$ts
  ]);

  $count++;
}

echo json_encode(["status"=>"ok","online_count"=>$count,"db"=>$db]);
