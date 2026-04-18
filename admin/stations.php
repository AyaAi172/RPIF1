<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireAdmin();
$title = "Admin Stations";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();
  $action = $_POST["action"] ?? "";

  if ($action === "create") {
    $serial = trim($_POST["serial"] ?? "");
    $name   = trim($_POST["name"] ?? "");
    $desc   = trim($_POST["description"] ?? "");

    if ($serial === "" || $name === "") $msg = "Serial and name required.";
    else {
      $stmt = mysqli_prepare($conn, "INSERT INTO stations (serial_number, name, description, user_id) VALUES (?,?,?,NULL)");
      mysqli_stmt_bind_param($stmt, "sss", $serial, $name, $desc);
      if (mysqli_stmt_execute($stmt)) $msg = "Station created (available).";
      else $msg = "Create failed (serial may exist).";
    }
  }

  if ($action === "assign") {
    $sid = (int)($_POST["station_id"] ?? 0);
    $uid = (int)($_POST["user_id"] ?? 0);
    if ($sid <= 0 || $uid <= 0) {
      $msg = "Choose a valid station and user.";
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE stations SET user_id=? WHERE station_id=?");
      mysqli_stmt_bind_param($stmt, "ii", $uid, $sid);
      mysqli_stmt_execute($stmt);
      $msg = "Assigned.";
    }
  }

  if ($action === "unassign") {
    $sid = (int)($_POST["station_id"] ?? 0);
    if ($sid <= 0) {
      $msg = "Invalid station.";
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE stations SET user_id=NULL WHERE station_id=?");
      mysqli_stmt_bind_param($stmt, "i", $sid);
      mysqli_stmt_execute($stmt);
      $msg = "Unassigned (available).";
    }
  }

  if ($action === "delete") {
    $sid = (int)($_POST["station_id"] ?? 0);
    if ($sid <= 0) {
      $msg = "Invalid station.";
    } else {
      $stmt = mysqli_prepare($conn, "DELETE FROM stations WHERE station_id=?");
      mysqli_stmt_bind_param($stmt, "i", $sid);
      mysqli_stmt_execute($stmt);
      $msg = "Deleted permanently.";
    }
  }
}

// users for dropdown
$users = [];
$res = mysqli_query($conn, "SELECT user_id, username, role FROM users ORDER BY role DESC, username");
while ($row = mysqli_fetch_assoc($res)) $users[] = $row;

// stations list
$stations = [];
$res = mysqli_query($conn, "
  SELECT s.station_id, s.serial_number, s.name, s.user_id, u.username AS owner
  FROM stations s
  LEFT JOIN users u ON s.user_id=u.user_id
  ORDER BY (s.user_id IS NOT NULL) DESC, s.station_id DESC
");
while ($row = mysqli_fetch_assoc($res)) $stations[] = $row;

require_once PIF_ROOT . "/includes/header.php";
?>

<h1 class="h3 mb-3">Admin - Stations</h1>

<?php if ($msg !== ""): ?>
  <div class="alert alert-info"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5">Create station</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">

        <input class="form-control mb-2"
          name="serial"
          placeholder="e.g. ST-4004-000"
          required>
        <input class="form-control mb-2" name="name" placeholder="Station name" required>
        <input class="form-control mb-3" name="description" placeholder="Description (optional)">

        <button class="btn btn-dark w-100">Create</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h5">All stations</h2>

      <?php if (count($stations) === 0): ?>
        <p class="empty-state">No stations yet.</p>
      <?php else: ?>
        <div class="management-card-list">
          <?php foreach ($stations as $s): ?>
            <?php $taken = ($s["user_id"] !== null); ?>
            <section class="management-card">
              <div class="management-card-header">
                <div>
                  <h3 class="management-card-title"><?= esc($s["name"]) ?></h3>
                  <p class="management-card-description"><?= esc($s["serial_number"]) ?></p>
                </div>
                <span class="badge rounded-pill <?= $taken ? "text-bg-danger" : "text-bg-success" ?>">
                  <?= $taken ? "Taken" : "Available" ?>
                </span>
              </div>

              <div class="management-card-meta three-col">
                <div class="management-meta-item">
                  <span class="management-meta-label">Station ID</span>
                  <span class="management-meta-value"><?= (int)$s["station_id"] ?></span>
                </div>
                <div class="management-meta-item">
                  <span class="management-meta-label">Owner</span>
                  <span class="management-meta-value"><?= $taken ? esc($s["owner"] ?? "unknown") : "Nobody assigned" ?></span>
                </div>
                <div class="management-meta-item">
                  <span class="management-meta-label">Status</span>
                  <span class="management-meta-value"><?= $taken ? "Assigned to a user" : "Ready to register" ?></span>
                </div>
              </div>

              <div class="management-card-actions">
                <form method="post" class="management-form">
                  <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                  <input type="hidden" name="action" value="assign">
                  <input type="hidden" name="station_id" value="<?= (int)$s["station_id"] ?>">

                  <div class="management-form-grid station-assign">
                    <div>
                      <label class="collection-inline-label">Assign to user</label>
                      <select class="form-select form-select-sm" name="user_id" required>
                        <option value="">Choose user...</option>
                        <?php foreach ($users as $u): ?>
                          <option value="<?= (int)$u["user_id"] ?>"><?= esc($u["username"]) ?> (<?= esc($u["role"]) ?>)</option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div>
                      <label class="collection-inline-label">&nbsp;</label>
                      <button class="btn btn-sm btn-outline-dark w-100">Assign station</button>
                    </div>
                  </div>
                </form>

                <div class="management-toolbar">
                  <form method="post" onsubmit="return confirm('Unassign station?');">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="unassign">
                    <input type="hidden" name="station_id" value="<?= (int)$s["station_id"] ?>">
                    <button class="btn btn-sm btn-outline-secondary">Unassign</button>
                  </form>

                  <form method="post" onsubmit="return confirm('Delete station permanently?');">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="station_id" value="<?= (int)$s["station_id"] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete station</button>
                  </form>
                </div>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once PIF_ROOT . "/includes/footer.php";
?>
