<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";


$title = "Register";
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password1 = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $full_name === '' || $email === '' || $password1 === '') {
        $error = "All fields are required.";
    } elseif ($password1 !== $password2) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password1, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?,?,?,?, 'user')");
        $stmt->bind_param("ssss", $username, $full_name, $email, $hashed);

        try {
            $stmt->execute();
            $success = "Account created. You can log in now.";
        } catch (mysqli_sql_exception $e) {
            // likely duplicate username/email
            $error = "Username or email already exists.";
        }
    }
}

require_once __DIR__ . "/../admin/includes/header.php";

?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <h1 class="h4 mb-3">Create account</h1>

      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Full name</label>
          <input class="form-control" name="full_name" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" required>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Confirm</label>
            <input class="form-control" type="password" name="password2" required>
          </div>
        </div>

        <button class="btn btn-dark w-100">Register</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . "/../admin/includes/footer.php";?>
