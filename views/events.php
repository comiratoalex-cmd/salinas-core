<?php
require "../auth.php";
require "../config/db.php";
$rows = $pdo->query("SELECT * FROM sl_events ORDER BY event_time DESC LIMIT 200")->fetchAll();
?>
<h2>Events</h2>
<table border="1">
<tr><th>Avatar</th><th>Type</th><th>Region</th><th>Parcel</th><th>Time</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= $r["avatar_name"] ?></td>
<td><?= $r["event_type"] ?></td>
<td><?= $r["region"] ?></td>
<td><?= $r["parcel"] ?></td>
<td><?= $r["event_time"] ?></td>
</tr>
<?php endforeach; ?>
</table>
