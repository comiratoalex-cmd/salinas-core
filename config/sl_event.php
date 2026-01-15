<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =======================
// CONFIG
// =======================
$host = "db5019378081.hosting-data.io";
$db   = "dbs15162566";
$user = "dbu2347265";
$pass = "Sunlight2024!Go";

// =======================
// CONEXÃO
// =======================
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB_CONNECTION_FAILED",
        "msg" => $e->getMessage()
    ]);
    exit;
}

// =======================
// LE JSON
// =======================
$raw = file_get_contents("php://input");

if (!$raw) {
    http_response_code(400);
    echo json_encode(["error" => "NO_BODY"]);
    exit;
}

$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "INVALID_JSON"]);
    exit;
}

// =======================
// VALIDA
// =======================
$required = [
    "avatar_uuid",
    "avatar_name",
    "event_type",
    "region",
    "parcel",
    "slurl",
    "timestamp"
];

foreach ($required as $r) {
    if (empty($data[$r])) {
        http_response_code(422);
        echo json_encode(["error" => "MISSING_$r"]);
        exit;
    }
}

$type = strtolower($data["event_type"]);
if (!in_array($type, ["entry", "exit"])) {
    http_response_code(422);
    echo json_encode(["error" => "INVALID_EVENT_TYPE"]);
    exit;
}

// =======================
// INSERT
// =======================
$stmt = $pdo->prepare("
    INSERT INTO sl_sessions (
        avatar_uuid,
        avatar_name,
        event_type,
        region,
        parcel,
        slurl,
        event_time
    ) VALUES (
        :uuid, :name, :type, :region, :parcel, :slurl, :time
    )
");

$stmt->execute([
    ":uuid"   => $data["avatar_uuid"],
    ":name"   => $data["avatar_name"],
    ":type"   => $type,
    ":region" => $data["region"],
    ":parcel" => $data["parcel"],
    ":slurl"  => $data["slurl"],
    ":time"   => $data["timestamp"]
]);

echo json_encode(["status" => "ok"]);
