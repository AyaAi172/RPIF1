<?php
require_once __DIR__ . "/../includes/auth.php";
requireAdmin();

$title = "Admin";
require_once __DIR__ . "/../includes/header.php";
?>
<h1 class="h3">Admin Dashboard</h1>
<p class="text-muted">Coming next: manage users/stations/measurements/collections.</p>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
