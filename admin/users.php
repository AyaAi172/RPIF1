<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireAdmin();
$title = "Admin Users";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();
  $action = $_POST["action"] ?? "";

  // Create user
  if ($action === "create") {
    $username  = trim($_POST["username"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $pass      = $_POST["password"] ?? "";
    $role      = ($_POST["role"] ?? "user") === "admin" ? "admin" : "user";

    if ($username === "" || $full_name === "" || $email === "" || $pass === "") {
      $msg = "Fill all fields.";
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = mysqli_prepare($conn, "INSERT INTO users (username, full_name, email, password, role) VALUES (?,?,?,?,?)");
      mysqli_stmt_bind_param($stmt, "sssss", $username, $full_name, $email, $hash, $role);
      if (mysqli_stmt_execute($stmt)) $msg = "User created.";
      else $msg = "Create failed (username/email may exist).";
    }
  }

  // Change role
  if ($action === "role") {
    $uid = (int)($_POST["user_id"] ?? 0);
    $role = ($_POST["role"] ?? "user") === "admin" ? "admin" : "user";

    if ($uid === (int)$_SESSION["user_id"]) {
      $msg = "You cannot change your own role while logged in.";
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE users SET role=? WHERE user_id=?");
      mysqli_stmt_bind_param($stmt, "si", $role, $uid);
      mysqli_stmt_execute($stmt);
      $msg = "Role updated.";
    }
  }

  // Reset password
  if ($action === "reset") {
    $uid = (int)($_POST["user_id"] ?? 0);
    $new = $_POST["new_password"] ?? "";
    if ($new === "") $msg = "Password is empty.";
    else {
      $hash = password_hash($new, PASSWORD_DEFAULT);
      $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
      mysqli_stmt_bind_param($stmt, "si", $hash, $uid);
      mysqli_stmt_execute($stmt);
      $msg = "Password reset.";
    }
  }

  // Delete user safely (unassign stations first)
  if ($action === "delete") {
    $uid = (int)($_POST["user_id"] ?? 0);
    if ($uid === (int)$_SESSION["user_id"]) {
      $msg = "You cannot delete yourself while logged in.";
    } else {
      mysqli_query($conn, "UPDATE stations SET user_id=NULL WHERE user_id=$uid");
      mysqli_query($conn, "DELETE FROM users WHERE user_id=$uid");
      $msg = "User deleted (stations became available).";
    }
  }
}

// Load users
$users = [];
$res = mysqli_query($conn, "SELECT user_id, username, full_name, email, role FROM users ORDER BY role DESC, username");
while ($row = mysqli_fetch_assoc($res)) $users[] = $row;

require_once PIF_ROOT . "/includes/header.php";
?>

<h1 class="h3 mb-3">Admin - Users</h1>

<?php if ($msg !== ""): ?>
  <div class="alert alert-info"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5">Create user</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">

        <input class="form-control mb-2" name="username" placeholder="username" required>
        <input class="form-control mb-2" name="full_name" placeholder="full name" required>
        <input class="form-control mb-2" type="email" name="email" placeholder="email" required>
        <input class="form-control mb-2" type="password" name="password" placeholder="password" required>

        <select class="form-select mb-3" name="role">
          <option value="user">user</option>
          <option value="admin">admin</option>
        </select>

        <button class="btn btn-dark w-100">Create</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h5">All users</h2>

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u["user_id"] ?></td>
                <td><?= esc($u["username"]) ?></td>
                <td><?= esc($u["full_name"]) ?></td>
                <td><?= esc($u["email"]) ?></td>
                <td><?= esc($u["role"]) ?></td>
                <td class="d-flex flex-wrap gap-2">

                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="role">
                    <input type="hidden" name="user_id" value="<?= (int)$u["user_id"] ?>">
                    <select class="form-select form-select-sm" name="role">
                      <option value="user" <?= $u["role"]==="user"?"selected":"" ?>>user</option>
                      <option value="admin" <?= $u["role"]==="admin"?"selected":"" ?>>admin</option>
                    </select>
                    <button class="btn btn-sm btn-outline-dark">Update</button>
                  </form>

                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="user_id" value="<?= (int)$u["user_id"] ?>">
                    <input class="form-control form-control-sm" type="password" name="new_password" placeholder="new pass" required>
                    <button class="btn btn-sm btn-outline-secondary">Reset</button>
                  </form>

                  <form method="post" onsubmit="return confirm('Delete user? Their stations become available.');">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= (int)$u["user_id"] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>

                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php require_once PIF_ROOT . "/includes/footer.php";
 ?>
