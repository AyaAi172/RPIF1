<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireAdmin();
$title = "Admin Measurements";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();
  $action = $_POST["action"] ?? "";
  if ($action === "delete") {
    $mid = (int)($_POST["measurement_id"] ?? 0);
    mysqli_query($conn, "DELETE FROM measurements WHERE measurement_id=$mid");
    $msg = "Measurement deleted.";
  }
}

// stations dropdown (all stations)
$stations = [];
$res = mysqli_query($conn, "SELECT station_id, serial_number, name FROM stations ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) $stations[] = $row;

$station_id = (int)($_GET["station_id"] ?? 0);
$start = $_GET["start"] ?? "";
$end   = $_GET["end"] ?? "";

function toSqlDateTime2($x) {
  $x = trim($x);
  if ($x === "") return "";
  return str_replace("T", " ", $x) . ":00";
}

$rows = [];
if (isset($_GET["filter"])) {
  $startSql = toSqlDateTime2($start);
  $endSql   = toSqlDateTime2($end);

  if ($station_id > 0 && $startSql !== "" && $endSql !== "") {
    $stmt = mysqli_prepare($conn, "
      SELECT measurement_id, measured_at, temperature, humidity, pressure, light, gas
      FROM measurements
      WHERE station_id=? AND measured_at BETWEEN ? AND ?
      ORDER BY measured_at DESC
      LIMIT 200
    ");
    mysqli_stmt_bind_param($stmt, "iss", $station_id, $startSql, $endSql);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
  }
}

require_once PIF_ROOT . "/includes/header.php";
?>

<h1 class="h3 mb-3">Admin - Measurements</h1>

<?php if ($msg !== ""): ?>
  <div class="alert alert-info"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
  <h2 class="h5">Filter</h2>
  <form method="get" class="row g-3 align-items-end">
    <input type="hidden" name="filter" value="1">

    <div class="col-md-4">
      <label class="form-label">Station</label>
      <select class="form-select" name="station_id" required>
        <option value="0">-- choose --</option>
        <?php foreach ($stations as $s): ?>
          <option value="<?= (int)$s["station_id"] ?>" <?= ((int)$s["station_id"] === $station_id) ? "selected" : "" ?>>
            <?= esc($s["name"]) ?> (<?= esc($s["serial_number"]) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Start</label>
      <input class="form-control" type="datetime-local" name="start" value="<?= esc($start) ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">End</label>
      <input class="form-control" type="datetime-local" name="end" value="<?= esc($end) ?>" required>
    </div>

    <div class="col-md-2">
      <button class="btn btn-dark w-100">Show</button>
    </div>
  </form>
</div>

<div class="card p-3">
  <h2 class="h5">Results</h2>

  <?php if (isset($_GET["filter"])): ?>
    <p class="text-muted">Showing up to 200 rows.</p>

    <?php if (count($rows) === 0): ?>
      <p class="text-muted mb-0">No data.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>Time</th><th>Temp</th><th>Hum</th><th>Press</th><th>Light</th><th>Gas</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= esc($r["measured_at"]) ?></td>
                <td><?= esc((string)$r["temperature"]) ?></td>
                <td><?= esc((string)$r["humidity"]) ?></td>
                <td><?= esc((string)$r["pressure"]) ?></td>
                <td><?= esc((string)$r["light"]) ?></td>
                <td><?= esc((string)$r["gas"]) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete this measurement?');">
                    <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="measurement_id" value="<?= (int)$r["measurement_id"] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-muted mb-0">Choose station and time range.</p>
  <?php endif; ?>
</div>

<?php require_once PIF_ROOT . "/includes/footer.php";
 ?>
