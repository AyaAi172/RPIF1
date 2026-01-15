<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireLogin();
$title = "Account";

$msg = "";
$user = getCurrentUser($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();

  $username  = trim($_POST["username"] ?? "");
  $full_name = trim($_POST["full_name"] ?? "");
  $email     = trim($_POST["email"] ?? "");

  $new1 = $_POST["new_password"] ?? "";
  $new2 = $_POST["new_password2"] ?? "";

  if ($username === "" || $full_name === "" || $email === "") {
    $msg = "Please fill all required fields.";
  } else if ($new1 !== "" && $new1 !== $new2) {
    $msg = "New passwords do not match.";
  } else {
    // update basic fields
    $stmt = mysqli_prepare($conn, "UPDATE users SET username=?, full_name=?, email=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $username, $full_name, $email, $_SESSION["user_id"]);
    $ok = mysqli_stmt_execute($stmt);

    // update password if user typed it
    if ($ok && $new1 !== "") {
      $hash = password_hash($new1, PASSWORD_DEFAULT);
      $stmt2 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
      mysqli_stmt_bind_param($stmt2, "si", $hash, $_SESSION["user_id"]);
      mysqli_stmt_execute($stmt2);
    }

    if ($ok) {
      $_SESSION["username"] = $username;
      $msg = "Account updated.";
      $user = getCurrentUser($conn);
    } else {
      $msg = "Update failed. Username/email may already exist.";
    }
  }
}

require_once PIF_ROOT . "/includes/header.php";
?>
<div class="card p-3">
  <h2 class="h5">My Account</h2>

  <?php if ($msg !== ""): ?>
    <div class="alert alert-info"><?= esc($msg) ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Username</label>
        <input class="form-control" name="username" value="<?= esc($user["username"]) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Full name</label>
        <input class="form-control" name="full_name" value="<?= esc($user["full_name"]) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?= esc($user["email"]) ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">New password (optional)</label>
        <input class="form-control" type="password" name="new_password">
      </div>

      <div class="col-md-6">
        <label class="form-label">Confirm new password</label>
        <input class="form-control" type="password" name="new_password2">
      </div>

      <div class="col-12">
        <button class="btn btn-dark">Save</button>
      </div>
    </div>
  </form>
</div>
<?php require_once PIF_ROOT . "/includes/footer.php";
 ?>
