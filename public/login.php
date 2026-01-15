<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
$title = "Login";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();

  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";

  $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ?");
  mysqli_stmt_bind_param($stmt, "s", $username);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  if ($row = mysqli_fetch_assoc($res)) {
    if (password_verify($password, $row["password"])) {
      $_SESSION["user_id"] = (int)$row["user_id"];
      $_SESSION["username"] = $row["username"];
      $_SESSION["role"] = $row["role"];

      header("Location: /RPIF1/user/welcome.php");
      exit();
    }
  }
  $msg = "Wrong username or password.";
}

require_once PIF_ROOT . "/includes/header.php";
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h1 class="h4 mb-3">Login</h1>

      <?php if ($msg !== ""): ?>
        <div class="alert alert-danger"><?= esc($msg) ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">

        <div class="mb-2">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="password" required>
        </div>

        <button class="btn btn-dark w-100">Login</button>
      </form>
    </div>
  </div>
</div>
<?php require_once PIF_ROOT . "/includes/footer.php";
 ?>
