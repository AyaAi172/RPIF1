<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";

$title = "Login";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: /RPIF1/user/welcome.php");
            exit();
        }
    }

    $error = "Invalid username or password.";
}

require_once __DIR__ . "/../admin/includes/header.php";
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h1 class="h4 mb-3">Login</h1>

      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="mb-3">
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
<?php require_once __DIR__ . "/../admin/includes/footer.php"; ?>
