<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireLogin();
$title = "Stations";

$msg = "";
$editId = (int)($_GET["edit"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();
  $action = $_POST["action"] ?? "";

  // Register by serial (only if available: user_id IS NULL)
  if ($action === "register") {
    $serial = trim($_POST["serial"] ?? "");
    if ($serial === "") {
      $msg = "Please type a serial number.";
    } else {
      $stmt = mysqli_prepare($conn, "SELECT station_id, user_id FROM stations WHERE serial_number=?");
      mysqli_stmt_bind_param($stmt, "s", $serial);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);

      if ($row = mysqli_fetch_assoc($res)) {
        if ($row["user_id"] !== null) {
          $msg = "This station is already taken.";
        } else {
          $sid = (int)$row["station_id"];
          $stmt2 = mysqli_prepare($conn, "UPDATE stations SET user_id=? WHERE station_id=? AND user_id IS NULL");
          mysqli_stmt_bind_param($stmt2, "ii", $_SESSION["user_id"], $sid);
          mysqli_stmt_execute($stmt2);
          $msg = "Station registered to your account.";
        }
      } else {
        $msg = "Serial number not found.";
      }
    }
  }

  // Save station edits (only if it belongs to you)
  if ($action === "save") {
    $sid = (int)($_POST["station_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $desc = trim($_POST["description"] ?? "");

    if ($sid <= 0 || $name === "") {
      $msg = "Name is required.";
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE stations SET name=?, description=? WHERE station_id=? AND user_id=?");
      mysqli_stmt_bind_param($stmt, "ssii", $name, $desc, $sid, $_SESSION["user_id"]);
      mysqli_stmt_execute($stmt);
      header("Location: /RPIF1/user/stations.php");
      exit();
    }
  }

  // Unassign station (your Delete meaning A)
  if ($action === "unassign") {
    $sid = (int)($_POST["station_id"] ?? 0);
    $stmt = mysqli_prepare($conn, "UPDATE stations SET user_id=NULL WHERE station_id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $sid, $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $msg = "Station removed and is available again.";
  }
}

// Load my stations
$myStations = [];
$stmt = mysqli_prepare($conn, "SELECT station_id, serial_number, name, description FROM stations WHERE user_id=? ORDER BY station_id DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $myStations[] = $row;

require_once PIF_ROOT . "/includes/header.php";
?>

<h1 class="h3 mb-3">Stations</h1>

<?php if ($msg !== ""): ?>
  <div class="alert alert-info"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5">Register station</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
        <input type="hidden" name="action" value="register">

        <label class="form-label">Serial number</label>
        <input
  class="form-control mb-2"
  name="serial"
  placeholder="e.g. ST-4004-000"
  required
>


        <button class="btn btn-dark w-100">Register</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h5">My stations</h2>

      <?php if (count($myStations) === 0): ?>
        <p class="empty-state">No stations yet.</p>
      <?php else: ?>
        <div class="management-card-list">
          <?php foreach ($myStations as $s): ?>
            <?php $sid = (int)$s["station_id"]; $isEdit = ($editId === $sid); ?>
            <section class="management-card">
              <div class="management-card-header">
                <div>
                  <h3 class="management-card-title"><?= esc($s["name"]) ?></h3>
                  <p class="management-card-description"><?= esc($s["serial_number"]) ?></p>
                </div>
                <span class="badge rounded-pill text-bg-dark">Owned by you</span>
              </div>

              <div class="management-card-meta three-col">
                <div class="management-meta-item">
                  <span class="management-meta-label">Station ID</span>
                  <span class="management-meta-value"><?= $sid ?></span>
                </div>
                <div class="management-meta-item">
                  <span class="management-meta-label">Serial number</span>
                  <span class="management-meta-value"><?= esc($s["serial_number"]) ?></span>
                </div>
                <div class="management-meta-item">
                  <span class="management-meta-label">Description</span>
                  <span class="management-meta-value"><?= esc($s["description"] ?: "No description added.") ?></span>
                </div>
              </div>

              <div class="management-card-actions">
                <?php if ($isEdit): ?>
                  <form method="post" class="management-form">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="station_id" value="<?= $sid ?>">

                    <div class="management-form-grid two-fields">
                      <div>
                        <label class="collection-inline-label">Station name</label>
                        <input class="form-control form-control-sm" name="name" value="<?= esc($s["name"]) ?>" required>
                      </div>
                      <div>
                        <label class="collection-inline-label">Description</label>
                        <input class="form-control form-control-sm" name="description" value="<?= esc($s["description"] ?? "") ?>">
                      </div>
                      <div>
                        <label class="collection-inline-label">&nbsp;</label>
                        <button class="btn btn-sm btn-outline-dark w-100">Save changes</button>
                      </div>
                    </div>
                  </form>

                  <div class="management-toolbar">
                    <a class="btn btn-sm btn-outline-secondary" href="/RPIF1/user/stations.php">Cancel</a>
                  </div>
                <?php else: ?>
                  <div class="management-toolbar">
                    <a class="btn btn-sm btn-outline-dark" href="/RPIF1/user/stations.php?edit=<?= $sid ?>">Edit</a>

                    <form method="post" onsubmit="return confirm('Remove this station? It becomes available again.');">
                      <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                      <input type="hidden" name="action" value="unassign">
                      <input type="hidden" name="station_id" value="<?= $sid ?>">
                      <button class="btn btn-sm btn-outline-danger">Remove station</button>
                    </form>
                  </div>
                <?php endif; ?>
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
