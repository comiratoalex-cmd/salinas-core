<?php
require "../auth.php";
require "../config/db.php";
$rows = $pdo->query("SELECT * FROM sl_avatars ORDER BY total_minutes DESC")->fetchAll();
?>
<h2>Avatars</h2>
<table border="1">
<tr><th>Name</th><th>Total Minutes</th><th>Sessions</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= $r["avatar_name"] ?></td>
<td><?= $r["total_minutes"] ?></td>
<td><?= $r["total_sessions"] ?></td>
</tr>
<?php endforeach; ?>
</table>
