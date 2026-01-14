<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
requireLogin();

$title = "Welcome";
require_once __DIR__ . "/../admin/includes/header.php";
?>
<h1 class="h3">Welcome</h1>
<p class="text-muted">Mini version: login works. Next: stations + measurements.</p>
<?php require_once __DIR__ . "/../admin/includes/footer.php";
 ?>
