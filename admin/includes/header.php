<?php
require_once __DIR__ . "/auth.php";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? "RPIF1") ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/RPIF1/admin/assets/css/app.css" rel="stylesheet">

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= isLoggedIn() ? "/RPIF1/user/welcome.php" : "/RPIF1/public/login.php" ?>">PIF</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/user/welcome.php">Welcome</a></li>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/user/stations.php">Stations</a></li>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/user/measurements.php">Measurements</a></li>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/user/account.php">Account</a></li>
        <li class="nav-item"><a class="nav-link" href="/RPIF1/admin/users.php">Admin Users</a></li>
<li class="nav-item"><a class="nav-link" href="/RPIF1/admin/stations.php">Admin Stations</a></li>


          <?php if (isAdmin()): ?>
            <li class="nav-item"><a class="nav-link" href="/RPIF1/admin/dashboard.php">Admin</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav">
        <?php if (!isLoggedIn()): ?>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/public/register.php">Register</a></li>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/public/login.php">Login</a></li>
        <?php else: ?>
          <li class="nav-item"><span class="navbar-text me-3">Hi, <?= e($_SESSION['username']) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="/RPIF1/public/logout.php">Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
