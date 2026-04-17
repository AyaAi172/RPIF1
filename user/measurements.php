<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireLogin();
$title = "Measurements";

$msg = "";
$rows = [];

// load my stations
$stations = [];
$stmt = mysqli_prepare($conn, "SELECT station_id, name, serial_number FROM stations WHERE user_id=? ORDER BY name");
mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $stations[] = $row;

$station_id = (int)($_GET["station_id"] ?? 0);
$start = $_GET["start"] ?? "";
$end   = $_GET["end"] ?? "";

if (isset($_GET["filter"])) {
  $startSql = toSqlDateTime($start);
  $endSql   = toSqlDateTime($end);

  if ($station_id <= 0) {
    $msg = "Please select a station.";
  } else if ($startSql === "" || $endSql === "") {
    $msg = "Please select start and end date/time.";
  } else {
    // make sure station belongs to this user
    $chk = mysqli_prepare($conn, "SELECT station_id FROM stations WHERE station_id=? AND user_id=?");
    mysqli_stmt_bind_param($chk, "ii", $station_id, $_SESSION["user_id"]);
    mysqli_stmt_execute($chk);
    $chkRes = mysqli_stmt_get_result($chk);

    if (!mysqli_fetch_assoc($chkRes)) {
      $msg = "Not allowed.";
    } else {
      $stmt = mysqli_prepare($conn, "
        SELECT measured_at, temperature, humidity, pressure, light, gas
        FROM measurements
        WHERE station_id=? AND measured_at BETWEEN ? AND ?
        ORDER BY measured_at DESC
        LIMIT 500
      ");
      mysqli_stmt_bind_param($stmt, "iss", $station_id, $startSql, $endSql);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    }
  }
}

require_once PIF_ROOT . "/includes/header.php";
?>

<h1 class="h3 mb-3">Measurements</h1>

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

  <?php if (isset($_GET["filter"]) && $msg === ""): ?>
    <p class="text-muted">Rows: <?= count($rows) ?> (max 500)</p>

    <?php if (count($rows) === 0): ?>
      <p class="text-muted mb-0">No data in this range.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>Time</th><th>Temp</th><th>Hum</th><th>Press</th><th>Light</th><th>Gas</th>
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
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-muted mb-0">Choose a station and time range.</p>
  <?php endif; ?>
</div>

<?php require_once PIF_ROOT . "/includes/footer.php";
 ?>
