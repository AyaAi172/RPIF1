<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";

requireLogin();
$title = "Stations";

$edit_station_id = (int)($_GET['edit'] ?? 0);

$error = "";
$success = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // 1) Register station by serial number (available = user_id IS NULL)
    if ($action === 'register_station') {
        $serial = trim($_POST['serial_number'] ?? '');

        if ($serial === '') {
            $error = "Serial number is required.";
        } else {
            // Find station
            $stmt = $conn->prepare("SELECT station_id, user_id FROM stations WHERE serial_number = ?");
            $stmt->bind_param("s", $serial);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows !== 1) {
                $error = "Serial number not found.";
            } else {
                $station = $res->fetch_assoc();
                $station_id = (int)$station['station_id'];
                $owner_id = $station['user_id']; // can be NULL

                if ($owner_id !== null) {
                    $error = "This station is already assigned to a user.";
                } else {
                    // Assign to current user (only if still NULL to avoid race condition)
                    $stmt2 = $conn->prepare("UPDATE stations SET user_id = ? WHERE station_id = ? AND user_id IS NULL");
                    $stmt2->bind_param("ii", $_SESSION['user_id'], $station_id);
                    $stmt2->execute();

                    if ($stmt2->affected_rows > 0) {
                        $success = "Station registered to your account.";
                    } else {
                        $error = "Registration failed. The station may have been taken already.";
                    }
                }
            }
        }
    }

    // 2) Save edits (only if station belongs to current user)
    if ($action === 'update_station') {
        $station_id = (int)($_POST['station_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($station_id <= 0 || $name === '') {
            $error = "Station name is required.";
        } else {
            $stmt = $conn->prepare("
                UPDATE stations
                SET name = ?, description = ?
                WHERE station_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ssii", $name, $description, $station_id, $_SESSION['user_id']);
            $stmt->execute();

            $success = "Station saved.";
            header("Location: /RPIF1/user/stations.php");
            exit();
        }
    }

    // 3) Unassign station (your “Delete” meaning A): set user_id = NULL
    if ($action === 'unassign_station') {
        $station_id = (int)($_POST['station_id'] ?? 0);

        if ($station_id <= 0) {
            $error = "Invalid station.";
        } else {
            $stmt = $conn->prepare("
                UPDATE stations
                SET user_id = NULL
                WHERE station_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $station_id, $_SESSION['user_id']);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success = "Station removed from your account and is available again.";
            } else {
                $error = "Remove failed (station not found or not yours).";
            }
        }
    }
}

// Fetch stations owned by current user
$stmt = $conn->prepare("SELECT station_id, serial_number, name, description FROM stations WHERE user_id = ? ORDER BY station_id DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . "/../admin/includes/header.php";
?>

<h1 class="h3 mb-3">Stations</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5">Register a station</h2>
      <p class="text-muted mb-2">Enter the serial number you received.</p>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="register_station">

        <div class="mb-3">
          <label class="form-label">Serial number</label>
          <input class="form-control" name="serial_number" required>
        </div>

        <button class="btn btn-dark w-100">Register</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h5">My stations</h2>

      <?php if (empty($stations)): ?>
        <p class="text-muted mb-0">No stations assigned to you yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Serial</th>
                <th>Name</th>
                <th>Description</th>
                <th style="width: 220px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stations as $s): ?>
                <?php
                  $sid = (int)$s['station_id'];
                  $isEditing = ($edit_station_id === $sid);
                ?>
                <tr>
                  <td><?= $sid ?></td>
                  <td><?= e($s['serial_number']) ?></td>

                  <?php if ($isEditing): ?>
                    <form method="post">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="update_station">
                      <input type="hidden" name="station_id" value="<?= $sid ?>">

                      <td><input class="form-control form-control-sm" name="name" value="<?= e($s['name']) ?>" required></td>
                      <td><input class="form-control form-control-sm" name="description" value="<?= e($s['description'] ?? '') ?>"></td>
                      <td class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-dark">Save</button>
                        <a class="btn btn-sm btn-outline-secondary" href="/RPIF1/user/stations.php">Cancel</a>
                      </td>
                    </form>
                  <?php else: ?>
                    <td><input class="form-control form-control-sm" value="<?= e($s['name']) ?>" readonly></td>
                    <td><input class="form-control form-control-sm" value="<?= e($s['description'] ?? '') ?>" readonly></td>
                    <td class="d-flex gap-2">
                      <a class="btn btn-sm btn-outline-dark" href="/RPIF1/user/stations.php?edit=<?= $sid ?>">Edit</a>

                      <form method="post" onsubmit="return confirm('Remove this station from your account? It will become available again.');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="unassign_station">
                        <input type="hidden" name="station_id" value="<?= $sid ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </td>
                  <?php endif; ?>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../admin/includes/footer.php"; ?>
