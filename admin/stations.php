<?php
require_once __DIR__ . "/./includes/db.php";
require_once __DIR__ . "/./includes/auth.php";
require_once __DIR__ . "/./includes/csrf.php";

requireAdmin();
$title = "Admin - Stations";

$error = "";
$success = "";

// ---------- HANDLE POST ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // A) Create new station (available by default because user_id NULL)
    if ($action === 'create_station') {
        $serial = trim($_POST['serial_number'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($serial === '' || $name === '') {
            $error = "Serial number and name are required.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO stations (serial_number, name, description, user_id) VALUES (?, ?, ?, NULL)");
                $stmt->bind_param("sss", $serial, $name, $desc);
                $stmt->execute();
                $success = "Station created (available).";
            } catch (mysqli_sql_exception $e) {
                $error = "Create failed. Serial number may already exist.";
            }
        }
    }

    // B) Assign station to user
    if ($action === 'assign_station') {
        $station_id = (int)($_POST['station_id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($station_id <= 0 || $user_id <= 0) {
            $error = "Invalid station or user.";
        } else {
            // Assign regardless of previous owner (admin override)
            $stmt = $conn->prepare("UPDATE stations SET user_id = ? WHERE station_id = ?");
            $stmt->bind_param("ii", $user_id, $station_id);
            $stmt->execute();
            $success = "Station assigned to user.";
        }
    }

    // C) Unassign station (make available)
    if ($action === 'unassign_station') {
        $station_id = (int)($_POST['station_id'] ?? 0);

        if ($station_id <= 0) {
            $error = "Invalid station.";
        } else {
            $stmt = $conn->prepare("UPDATE stations SET user_id = NULL WHERE station_id = ?");
            $stmt->bind_param("i", $station_id);
            $stmt->execute();
            $success = "Station unassigned (available).";
        }
    }

    // D) Delete station (hard delete)
    if ($action === 'delete_station') {
        $station_id = (int)($_POST['station_id'] ?? 0);

        if ($station_id <= 0) {
            $error = "Invalid station.";
        } else {
            $stmt = $conn->prepare("DELETE FROM stations WHERE station_id = ?");
            $stmt->bind_param("i", $station_id);
            $stmt->execute();
            $success = "Station deleted permanently.";
        }
    }
}

// ---------- LOAD USERS (for assign dropdown) ----------
$usersStmt = $conn->prepare("SELECT user_id, username, role FROM users ORDER BY role DESC, username ASC");
$usersStmt->execute();
$users = $usersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---------- LOAD STATIONS (with owner username) ----------
$sql = "
SELECT s.station_id, s.serial_number, s.name, s.description, s.user_id,
       u.username AS owner_username
FROM stations s
LEFT JOIN users u ON s.user_id = u.user_id
ORDER BY (s.user_id IS NOT NULL) DESC, s.station_id DESC
";
$stations = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . "/./includes/header.php";
?>

<h1 class="h3 mb-3">Admin - Stations</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="row g-3">
  <!-- Create station -->
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5">Create station (inventory)</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create_station">

        <div class="mb-2">
          <label class="form-label">Serial number</label>
          <input class="form-control" name="serial_number" placeholder="WST-202601-001" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Default name</label>
          <input class="form-control" name="name" placeholder="Station name" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Default description</label>
          <input class="form-control" name="description" placeholder="Optional">
        </div>

        <button class="btn btn-dark w-100">Create</button>
      </form>
      <p class="text-muted mt-2 mb-0">
        New stations are created as <strong>Available</strong> (no owner).
      </p>
    </div>
  </div>

  <!-- Stations list -->
  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h5">All stations</h2>

      <?php if (empty($stations)): ?>
        <p class="text-muted mb-0">No stations found.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Serial</th>
                <th>Name</th>
                <th>Status</th>
                <th>Owner</th>
                <th style="width: 340px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stations as $s): ?>
                <?php
                  $sid = (int)$s['station_id'];
                  $isTaken = ($s['user_id'] !== null);
                ?>
                <tr>
                  <td><?= $sid ?></td>
                  <td><?= e($s['serial_number']) ?></td>
                  <td><?= e($s['name']) ?></td>
                  <td>
                    <?php if ($isTaken): ?>
                      <span class="badge bg-danger">Taken</span>
                    <?php else: ?>
                      <span class="badge bg-success">Available</span>
                    <?php endif; ?>
                  </td>
                  <td><?= $isTaken ? e($s['owner_username'] ?? 'unknown') : '-' ?></td>

                  <td class="d-flex flex-wrap gap-2">
                    <!-- Assign -->
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="assign_station">
                      <input type="hidden" name="station_id" value="<?= $sid ?>">

                      <select class="form-select form-select-sm" name="user_id" required>
                        <option value="">Assign to...</option>
                        <?php foreach ($users as $u): ?>
                          <option value="<?= (int)$u['user_id'] ?>">
                            <?= e($u['username']) ?> (<?= e($u['role']) ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>

                      <button class="btn btn-sm btn-outline-dark">Assign</button>
                    </form>

                    <!-- Unassign -->
                    <form method="post" onsubmit="return confirm('Unassign this station? It becomes available again.');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="unassign_station">
                      <input type="hidden" name="station_id" value="<?= $sid ?>">
                      <button class="btn btn-sm btn-outline-secondary">Unassign</button>
                    </form>

                    <!-- Delete -->
                    <form method="post" onsubmit="return confirm('Delete this station permanently? This cannot be undone.');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_station">
                      <input type="hidden" name="station_id" value="<?= $sid ?>">
                      <button class="btn btn-sm btn-outline-danger">Delete</button>
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

<?php require_once __DIR__ . "/./includes/footer.php"; ?>
