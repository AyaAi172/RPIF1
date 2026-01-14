<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";

requireLogin();
$title = "Stations";

// IMPORTANT: set this to the user_id of the 'unassigned' user you created
$UNASSIGNED_USER_ID = 2; // TODO: change this (e.g., 2)

$error = "";
$success = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? '';

    // 1) Update station name/description (only if station belongs to current user)
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

            if ($stmt->affected_rows > 0) {
                $success = "Station updated.";
            } else {
                $error = "Update failed (station not found or not yours).";
            }
        }
    }

    // 2) Register station by serial number (only if owned by UNASSIGNED_USER_ID)
    if ($action === 'register_station') {
        if ($UNASSIGNED_USER_ID <= 0) {
            $error = "UNASSIGNED_USER_ID is not set yet. Ask teacher / check database.";
        } else {
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
                    $owner_id = (int)$station['user_id'];

                    if ($owner_id !== $UNASSIGNED_USER_ID) {
                        $error = "This station is already assigned to a user.";
                    } else {
                        // Assign to current user
                        $stmt2 = $conn->prepare("UPDATE stations SET user_id = ? WHERE station_id = ? AND user_id = ?");
                        $stmt2->bind_param("iii", $_SESSION['user_id'], $station_id, $UNASSIGNED_USER_ID);
                        $stmt2->execute();

                        if ($stmt2->affected_rows > 0) {
                            $success = "Station registered to your account.";
                        } else {
                            $error = "Registration failed. Try again.";
                        }
                    }
                }
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
                <th style="width: 120px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stations as $s): ?>
                <tr>
                  <td><?= (int)$s['station_id'] ?></td>
                  <td><?= e($s['serial_number']) ?></td>
                  <td>
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="update_station">
                      <input type="hidden" name="station_id" value="<?= (int)$s['station_id'] ?>">

                      <input class="form-control form-control-sm" name="name" value="<?= e($s['name']) ?>" required>
                  </td>
                  <td>
                      <input class="form-control form-control-sm" name="description" value="<?= e($s['description'] ?? '') ?>">
                  </td>
                  <td>
                      <button class="btn btn-sm btn-outline-dark">Save</button>
                    </form>
                  </td>
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
