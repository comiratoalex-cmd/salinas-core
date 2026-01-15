<?php
// sl_event.php
declare(strict_types=1);

require __DIR__ . "/config/db.php";

// ---------- helpers ----------
function bad(int $code, array $payload) {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

$raw = file_get_contents("php://input");
if (!$raw) bad(400, ["status"=>"no_body"]);

$data = json_decode($raw, true);
if (!is_array($data)) bad(400, ["status"=>"invalid_json","raw"=>$raw]);

$req = ["avatar_uuid","avatar_name","event_type","region","parcel","slurl","timestamp"];
foreach ($req as $k) {
  if (!isset($data[$k]) || trim((string)$data[$k]) === "") {
    bad(422, ["status"=>"missing_field","field"=>$k]);
  }
}

$uuid   = trim((string)$data["avatar_uuid"]);
$name   = trim((string)$data["avatar_name"]);
$type   = strtolower(trim((string)$data["event_type"]));
$region = trim((string)$data["region"]);
$parcel = trim((string)$data["parcel"]);
$slurl  = trim((string)$data["slurl"]);
$ts     = trim((string)$data["timestamp"]); // "YYYY-MM-DD HH:MM:SS"

// normaliza
if ($type === "entrada" || $type === "in") $type = "entry";
if ($type === "saida"   || $type === "out") $type = "exit";

if ($type !== "entry" && $type !== "exit") {
  bad(422, ["status"=>"invalid_event_type","got"=>$type]);
}

// imagem (não baixa nada, só salva link)
$avatarImage = "https://world.secondlife.com/resident/{$uuid}/image";

try {
  // garante transação
  $pdo->beginTransaction();

  if ($type === "entry") {
    // 1) se já existe sessão aberta, não cria outra (evita duplicação por lag/repeat)
    $check = $pdo->prepare("
      SELECT id FROM sl_sessions
      WHERE avatar_uuid = :uuid AND exit_time IS NULL
      ORDER BY entry_time DESC
      LIMIT 1
    ");
    $check->execute([":uuid"=>$uuid]);
    $open = $check->fetch();

    if ($open) {
      // atualiza “info” (nome/region/parcel/slurl/img) e mantém sessão aberta
      $upd = $pdo->prepare("
        UPDATE sl_sessions
        SET avatar_name = :name,
            avatar_image = :img,
            region = :region,
            parcel = :parcel,
            slurl = :slurl
        WHERE id = :id
      ");
      $upd->execute([
        ":name"=>$name, ":img"=>$avatarImage, ":region"=>$region, ":parcel"=>$parcel, ":slurl"=>$slurl, ":id"=>$open["id"]
      ]);

      $pdo->commit();
      echo json_encode(["status"=>"ok","logged"=>"entry","note"=>"already_open"]);
      exit;
    }

    // 2) cria a sessão
    $ins = $pdo->prepare("
      INSERT INTO sl_sessions
        (avatar_uuid, avatar_name, avatar_image, region, parcel, slurl, entry_time)
      VALUES
        (:uuid, :name, :img, :region, :parcel, :slurl, :entry_time)
    ");
    $ins->execute([
      ":uuid"=>$uuid,
      ":name"=>$name,
      ":img"=>$avatarImage,
      ":region"=>$region,
      ":parcel"=>$parcel,
      ":slurl"=>$slurl,
      ":entry_time"=>$ts
    ]);

    $pdo->commit();
    echo json_encode(["status"=>"ok","logged"=>"entry"]);
    exit;
  }

  // EXIT:
  // fecha a ÚLTIMA sessão aberta
  $upd = $pdo->prepare("
    UPDATE sl_sessions
    SET exit_time = :exit_time,
        duration_minutes = GREATEST(0, TIMESTAMPDIFF(MINUTE, entry_time, :exit_time))
    WHERE avatar_uuid = :uuid
      AND exit_time IS NULL
    ORDER BY entry_time DESC
    LIMIT 1
  ");
  $upd->execute([":uuid"=>$uuid, ":exit_time"=>$ts]);

  if ($upd->rowCount() === 0) {
    // não existia sessão aberta -> não cria linha nova (pra não sujar a tabela)
    $pdo->commit();
    echo json_encode(["status"=>"ok","logged"=>"exit","note"=>"no_open_session"]);
    exit;
  }

  $pdo->commit();
  echo json_encode(["status"=>"ok","logged"=>"exit"]);
  exit;

} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  bad(500, ["status"=>"db_error","message"=>$e->getMessage()]);
}