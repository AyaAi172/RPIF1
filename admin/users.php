<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";

requireAdmin();
$title = "Admin - Users";

$error = "";
$success = "";

// Prevent admin from deleting themselves by mistake
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

// ---------- HANDLE POST ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // A) Create user
    if ($action === 'create_user') {
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if ($username === '' || $full_name === '' || $email === '' || $password === '') {
            $error = "All fields are required to create a user.";
        } else {
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?,?,?,?,?)");
                $stmt->bind_param("sssss", $username, $full_name, $email, $hashed, $role);
                $stmt->execute();
                $success = "User created.";
            } catch (mysqli_sql_exception $e) {
                $error = "Create failed. Username or email may already exist.";
            }
        }
    }

    // B) Promote / demote user role
    if ($action === 'set_role') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if ($user_id <= 0) {
            $error = "Invalid user.";
        } elseif ($user_id === $currentAdminId && $role !== 'admin') {
            $error = "You cannot remove your own admin rights while logged in.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->bind_param("si", $role, $user_id);
            $stmt->execute();
            $success = "Role updated.";
        }
    }

    // C) Reset password
    if ($action === 'reset_password') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';

        if ($user_id <= 0 || $new_password === '') {
            $error = "Invalid user or empty password.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $success = "Password reset.";
        }
    }

    // D) Delete user SAFELY:
    // 1) Unassign stations (user_id = NULL)
    // 2) Delete user
    if ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            $error = "Invalid user.";
        } elseif ($user_id === $currentAdminId) {
            $error = "You cannot delete yourself while logged in.";
        } else {
            try {
                $conn->begin_transaction();

                // Unassign stations so inventory is not lost
                $stmt1 = $conn->prepare("UPDATE stations SET user_id = NULL WHERE user_id = ?");
                $stmt1->bind_param("i", $user_id);
                $stmt1->execute();

                // Delete user
                $stmt2 = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();

                $conn->commit();
                $success = "User deleted (stations unassigned).";
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $error = "Delete failed due to database constraints.";
            }
        }
    }
}

// ---------- LOAD ALL USERS + STATION COUNT ----------
$sql = "
SELECT u.user_id, u.username, u.full_name, u.email, u.role,
       (SELECT COUNT(*) FROM stations s WHERE s.user_id = u.user_id) AS station_count
FROM users u
ORDER BY (u.role = 'admin') DESC, u.username ASC
";
$users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . "/includes/header.php";
?>

<h1 class="h3 mb-3">Admin - Users</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="row g-3">
  <!-- Create user -->
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5">Create user</h2>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create_user">

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
          <label class="form-label">Role</label>
          <select class="form-select" name="role">
            <option value="user">user</option>
            <option value="admin">admin</option>
          </select>
        </div>

        <button class="btn btn-dark w-100">Create</button>
      </form>
    </div>
  </div>

  <!-- Users list -->
  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h5">All users</h2>

      <?php if (empty($users)): ?>
        <p class="text-muted mb-0">No users found.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Stations</th>
                <th style="width: 360px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <?php
                  $uid = (int)$u['user_id'];
                  $isSelf = ($uid === $currentAdminId);
                ?>
                <tr>
                  <td><?= $uid ?></td>
                  <td><?= e($u['username']) ?></td>
                  <td><?= e($u['full_name']) ?></td>
                  <td><?= e($u['email']) ?></td>
                  <td>
                    <?php if ($u['role'] === 'admin'): ?>
                      <span class="badge bg-dark">admin</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">user</span>
                    <?php endif; ?>
                  </td>
                  <td><?= (int)$u['station_count'] ?></td>
                  <td class="d-flex flex-wrap gap-2">

                    <!-- Set role -->
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="set_role">
                      <input type="hidden" name="user_id" value="<?= $uid ?>">

                      <select class="form-select form-select-sm" name="role" <?= $isSelf ? 'disabled' : '' ?>>
                        <option value="user" <?= ($u['role'] === 'user') ? 'selected' : '' ?>>user</option>
                        <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>admin</option>
                      </select>

                      <button class="btn btn-sm btn-outline-dark" <?= $isSelf ? 'disabled' : '' ?>>Update</button>
                    </form>

                    <!-- Reset password -->
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="reset_password">
                      <input type="hidden" name="user_id" value="<?= $uid ?>">

                      <input class="form-control form-control-sm" type="password" name="new_password"
                             placeholder="New password" required>

                      <button class="btn btn-sm btn-outline-secondary">Reset</button>
                    </form>

                    <!-- Delete user -->
                    <form method="post"
                          onsubmit="return confirm('Delete this user? Their stations will be unassigned (available again).');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= $uid ?>">
                      <button class="btn btn-sm btn-outline-danger" <?= $isSelf ? 'disabled' : '' ?>>Delete</button>
                    </form>

                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <p class="text-muted mt-2 mb-0">
        Delete user is safe: stations are set to <code>NULL</code> owner first (available again).
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
