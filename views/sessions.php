<?php
require "../auth.php";
require "../config/db.php";
$rows = $pdo->query("SELECT * FROM sl_sessions ORDER BY entry_time DESC LIMIT 200")->fetchAll();
?>
<h2>Sessions</h2>
<table border="1">
<tr><th>Avatar</th><th>Entry</th><th>Exit</th><th>Minutes</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= $r["avatar_name"] ?></td>
<td><?= $r["entry_time"] ?></td>
<td><?= $r["exit_time"] ?></td>
<td><?= $r["duration_minutes"] ?></td>
</tr>
<?php endforeach; ?>
</table>
