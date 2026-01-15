<?php require "auth.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Salinas Dashboard</title>
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<h1>Salinas Dashboard</h1>
<nav>
<a href="views/events.php">Events</a>
<a href="views/sessions.php">Sessions</a>
<a href="views/avatars.php">Avatars</a>
<a href="views/logs.php">Logs</a>
<a href="logout.php">Logout</a>
</nav>
<p>Welcome, <?php echo $_SESSION["user"]; ?></p>
</body>
</html>
