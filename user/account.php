<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";

requireLogin();
$title = "Account";

$error = "";
$success = "";

// Load current user data
$stmt = $conn->prepare("SELECT username, full_name, email, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    $new_password1 = $_POST['new_password'] ?? '';
    $new_password2 = $_POST['new_password2'] ?? '';

    if ($username === '' || $full_name === '' || $email === '') {
        $error = "Username, full name, and email are required.";
    } elseif ($new_password1 !== '' && $new_password1 !== $new_password2) {
        $error = "New passwords do not match.";
    } else {
        try {
            // Update basic fields
            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $username, $full_name, $email, $_SESSION['user_id']);
            $stmt->execute();

            // Update password only if provided
            if ($new_password1 !== '') {
                $hashed = password_hash($new_password1, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt2->bind_param("si", $hashed, $_SESSION['user_id']);
                $stmt2->execute();
            }

            // Keep session username in sync
            $_SESSION['username'] = $username;

            $success = "Account updated successfully.";

            // Reload updated data for display
            $stmt = $conn->prepare("SELECT username, full_name, email, role FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

        } catch (mysqli_sql_exception $e) {
            // Most common: duplicate username/email because both are UNIQUE
            $error = "Update failed. Username or email may already be in use.";
        }
    }
}

require_once __DIR__ . "/../admin/includes/header.php";
?>

<h1 class="h3 mb-3">My Account</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card p-3">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Username</label>
        <input class="form-control" name="username" value="<?= e($user['username']) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Full name</label>
        <input class="form-control" name="full_name" value="<?= e($user['full_name']) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">New password (leave empty to keep current)</label>
        <input class="form-control" type="password" name="new_password" autocomplete="new-password">
      </div>

      <div class="col-md-6">
        <label class="form-label">Confirm new password</label>
        <input class="form-control" type="password" name="new_password2" autocomplete="new-password">
      </div>

      <div class="col-12">
        <button class="btn btn-dark">Save changes</button>
      </div>
    </div>
  </form>
</div>

<?php require_once __DIR__ . "/../admin/includes/footer.php"; ?>
