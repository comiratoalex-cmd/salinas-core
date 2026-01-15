<?php
require_once __DIR__."/config/db.php";

$stmt = $pdo->prepare("
  INSERT INTO sl_sessions
  (avatar_uuid, avatar_name, region, parcel, entry_time)
  VALUES ('TEST-UUID', 'TEST AVATAR', 'REGION', 'PARCEL', NOW())
");

$stmt->execute();

echo "INSERIU";
