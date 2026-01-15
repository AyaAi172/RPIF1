<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
$title = "Register";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();

  $username  = trim($_POST["username"] ?? "");
  $full_name = trim($_POST["full_name"] ?? "");
  $email     = trim($_POST["email"] ?? "");
  $pass1     = $_POST["password"] ?? "";
  $pass2     = $_POST["password2"] ?? "";

  if ($username === "" || $full_name === "" || $email === "" || $pass1 === "") {
    $msg = "Please fill all fields.";
  } else if ($pass1 !== $pass2) {
    $msg = "Passwords do not match.";
  } else {
    $hash = password_hash($pass1, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, full_name, email, password, role) VALUES (?,?,?,?, 'user')");
    mysqli_stmt_bind_param($stmt, "ssss", $username, $full_name, $email, $hash);

    if (mysqli_stmt_execute($stmt)) {
      $msg = "Account created. You can login now.";
    } else {
      $msg = "Username or email already exists.";
    }
  }
}

require_once PIF_ROOT . "/includes/header.php";
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <h1 class="h4 mb-3">Register</h1>

      <?php if ($msg !== ""): ?>
        <div class="alert alert-info"><?= esc($msg) ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">

        <div class="mb-2">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Full name</label>
          <input class="form-control" name="full_name" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="password" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <input class="form-control" type="password" name="password2" required>
        </div>

        <button class="btn btn-dark w-100">Create account</button>
      </form>
    </div>
  </div>
</div>
<?php require_once PIF_ROOT . "/includes/footer.php";
 ?>
