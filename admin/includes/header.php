<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
$title = $title ?? "RPIF1";

$currentTheme = getThemePreference($conn);
$cssFile = PIF_ROOT . "/assets/css/app.css";
$cssVersion = file_exists($cssFile) ? filemtime($cssFile) : time();
$currentPath = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "toggle_theme") {
  checkCsrf();

  $nextTheme = ($currentTheme === "dark") ? "light" : "dark";
  saveThemePreference($conn, $nextTheme);

  $redirect = $_POST["redirect_to"] ?? $_SERVER["REQUEST_URI"] ?? "/RPIF1/public/login.php";
  header("Location: " . $redirect);
  exit();
}

$pendingFriendRequests = 0;
if (isLoggedIn() && hasTable($conn, "friend_requests")) {
  $stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS total
    FROM friend_requests
    WHERE receiver_user_id = ? AND status = 'pending'
  ");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    $pendingFriendRequests = (int)($row["total"] ?? 0);
  }
}

if (!function_exists("navLinkClass")) {
  function navLinkClass($href, $currentPath) {
    $base = "nav-link pif-nav-link";
    return $base . ($href === $currentPath ? " active" : "");
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= esc($currentTheme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title) ?></title>

  <!-- Bootstrap for quick clean UI -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/RPIF1/admin/assets/css/app.css?v=<?= esc((string)$cssVersion) ?>" rel="stylesheet">
</head>
<body class="theme-<?= esc($currentTheme) ?>">

<nav class="navbar navbar-expand-lg pif-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= isLoggedIn() ? "/RPIF1/user/welcome.php" : "/RPIF1/public/login.php" ?>">PIF</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/user/welcome.php", $currentPath) ?>" href="/RPIF1/user/welcome.php">Welcome</a></li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/user/stations.php", $currentPath) ?>" href="/RPIF1/user/stations.php">Stations</a></li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/user/measurements.php", $currentPath) ?>" href="/RPIF1/user/measurements.php">Measurements</a></li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/user/collections.php", $currentPath) ?>" href="/RPIF1/user/collections.php">Collections</a></li>
          <li class="nav-item">
            <a class="<?= navLinkClass("/RPIF1/user/friends.php", $currentPath) ?> d-flex align-items-center gap-2" href="/RPIF1/user/friends.php">
              <span>Friends</span>
              <?php if ($pendingFriendRequests > 0): ?>
                <span class="badge rounded-pill bg-danger"><?= $pendingFriendRequests ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/user/account.php", $currentPath) ?>" href="/RPIF1/user/account.php">Account</a></li>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
          <?php
          $adminPaths = [
            "/RPIF1/admin/dashboard.php",
            "/RPIF1/admin/users.php",
            "/RPIF1/admin/stations.php",
            "/RPIF1/admin/measurements.php",
            "/RPIF1/admin/collections.php",
          ];
          $adminActive = in_array($currentPath, $adminPaths, true);
          ?>
          <li class="nav-item dropdown">
            <a
              class="nav-link pif-nav-link dropdown-toggle<?= $adminActive ? " active" : "" ?>"
              href="#"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              Admin
            </a>
            <ul class="dropdown-menu pif-dropdown-menu">
              <li><a class="dropdown-item<?= $currentPath === "/RPIF1/admin/dashboard.php" ? " active" : "" ?>" href="/RPIF1/admin/dashboard.php">Dashboard</a></li>
              <li><a class="dropdown-item<?= $currentPath === "/RPIF1/admin/users.php" ? " active" : "" ?>" href="/RPIF1/admin/users.php">Users</a></li>
              <li><a class="dropdown-item<?= $currentPath === "/RPIF1/admin/stations.php" ? " active" : "" ?>" href="/RPIF1/admin/stations.php">Stations</a></li>
              <li><a class="dropdown-item<?= $currentPath === "/RPIF1/admin/measurements.php" ? " active" : "" ?>" href="/RPIF1/admin/measurements.php">Measurements</a></li>
              <li><a class="dropdown-item<?= $currentPath === "/RPIF1/admin/collections.php" ? " active" : "" ?>" href="/RPIF1/admin/collections.php">Collections</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav">
        <?php if (!isLoggedIn()): ?>
          <li class="nav-item me-2">
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="action" value="toggle_theme">
              <input type="hidden" name="redirect_to" value="<?= esc($_SERVER["REQUEST_URI"] ?? "/RPIF1/public/login.php") ?>">
              <button class="btn btn-sm btn-outline-light" type="submit"><?= $currentTheme === "dark" ? "Light mode" : "Dark mode" ?></button>
            </form>
          </li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/public/register.php", $currentPath) ?>" href="/RPIF1/public/register.php">Register</a></li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/public/login.php", $currentPath) ?>" href="/RPIF1/public/login.php">Login</a></li>
        <?php else: ?>
          <li class="nav-item me-2">
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
              <input type="hidden" name="action" value="toggle_theme">
              <input type="hidden" name="redirect_to" value="<?= esc($_SERVER["REQUEST_URI"] ?? "/RPIF1/user/welcome.php") ?>">
              <button class="btn btn-sm btn-outline-light" type="submit"><?= $currentTheme === "dark" ? "Light mode" : "Dark mode" ?></button>
            </form>
          </li>
          <li class="nav-item"><span class="navbar-text me-3">Hi, <?= esc($_SESSION["username"]) ?></span></li>
          <li class="nav-item"><a class="<?= navLinkClass("/RPIF1/public/logout.php", $currentPath) ?>" href="/RPIF1/public/logout.php">Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
