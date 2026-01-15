<?php
require "../auth.php";
require "../config/db.php";
$rows = $pdo->query("SELECT * FROM sl_logs ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>
<h2>Logs</h2>
<table border="1">
<tr><th>Source</th><th>Message</th><th>Date</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= $r["source"] ?></td>
<td><?= $r["message"] ?></td>
<td><?= $r["created_at"] ?></td>
</tr>
<?php endforeach; ?>
</table>
