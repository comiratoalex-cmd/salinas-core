<?php
$data = [
  "avatar_uuid" => "11111111-2222-3333-4444-555555555555",
  "avatar_name" => "TEST AVATAR",
  "event_type"  => "entry",
  "region"      => "TEST REGION",
  "parcel"      => "TEST PARCEL",
  "slurl"       => "https://maps.secondlife.com/secondlife/Test/128/128/25",
  "timestamp"   => "2026-01-12 16:10:00"
];

$ch = curl_init("https://comialsystem.com/sl_event.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

echo curl_exec($ch);