<?php
// api/avatar_img.php
// Proxy da foto do perfil do SL (usa avatar_uuid -> resident page -> meta imageid -> secondlife app image)

ini_set('display_errors', 0);
error_reporting(0);

$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';
if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
  http_response_code(400);
  header("Content-Type: text/plain; charset=utf-8");
  echo "bad_uuid";
  exit;
}

function curl_get($url, $timeout = 6) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT => "SALINAS/1.0 (+avatar proxy)",
    CURLOPT_HTTPHEADER => ["Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"]
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);
  return [$code, $ct, $body];
}

function output_fallback() {
  // 1) Se você tiver um placeholder local, use ele:
  // readfile(__DIR__ . "/../assets/img/avatar_placeholder.png");

  // 2) fallback simples (1x1 png)
  header("Content-Type: image/png");
  header("Cache-Control: public, max-age=300");
  echo base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/ax2pGQAAAAASUVORK5CYII=");
  exit;
}

// Cache simples (reduz chamadas no SL)
$cacheDir = __DIR__ . "/../cache/avatars";
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
$cacheFile = $cacheDir . "/" . $uuid . ".jpg";

// Se cache existe e é recente (< 6h), retorna
if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 6 * 3600)) {
  header("Content-Type: image/jpeg");
  header("Cache-Control: public, max-age=21600");
  readfile($cacheFile);
  exit;
}

// 1) Busca a página do residente pelo UUID (funciona)
$residentUrl = "https://world.secondlife.com/resident/" . $uuid;
[$code, $ct, $html] = curl_get($residentUrl, 8);

if ($code < 200 || $code >= 300 || !$html) {
  output_fallback();
}

// Se não tem foto (muitas páginas usam blank.jpg quando não existe foto)
if (stripos($html, "blank.jpg") !== false) {
  output_fallback();
}

// 2) Extrai meta imageid
$imageid = null;
if (preg_match('/<meta\s+name="imageid"\s+content="([^"]+)"/i', $html, $m)) {
  $imageid = trim($m[1]);
}

if (!$imageid || !preg_match('/^[0-9a-fA-F-]{36}$/', $imageid)) {
  output_fallback();
}

// 3) Baixa a imagem real pelo imageid
// Esse padrão aparece em scripts/relays antigos de profile pic. :contentReference[oaicite:1]{index=1}
$imgUrl = "https://secondlife.com/app/image/" . $imageid . "/1";
[$code2, $ct2, $imgBody] = curl_get($imgUrl, 8);

if ($code2 < 200 || $code2 >= 300 || !$imgBody) {
  output_fallback();
}

// Alguns servers mandam image/jpeg, outros octet-stream; vamos aceitar se veio conteúdo
header("Content-Type: image/jpeg");
header("Cache-Control: public, max-age=21600");

// Salva cache (best-effort)
@file_put_contents($cacheFile, $imgBody);

echo $imgBody;
exit;
