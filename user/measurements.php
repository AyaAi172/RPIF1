<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";

requireLogin();
$title = "Measurements";

$error = "";
$rows = [];

// 1) Load user's stations for dropdown
$stmt = $conn->prepare("SELECT station_id, serial_number, name FROM stations WHERE user_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2) Default form values
$station_id = (int)($_GET['station_id'] ?? 0);
$start_at   = $_GET['start_at'] ?? '';
$end_at     = $_GET['end_at'] ?? '';

// Helper: validate datetime-local input ("YYYY-MM-DDTHH:MM")
function toSqlDatetime(string $dtLocal): ?string {
    $dtLocal = trim($dtLocal);
    if ($dtLocal === '') return null;
    // expected format: 2026-01-18T14:30
    $dtLocal = str_replace('T', ' ', $dtLocal) . ":00"; // add seconds
    // basic validation
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $dtLocal)) return null;
    return $dtLocal;
}

// 3) If user submitted filter, fetch measurement rows
if (isset($_GET['filter'])) {
    $startSql = toSqlDatetime($start_at);
    $endSql   = toSqlDatetime($end_at);

    if ($station_id <= 0) {
        $error = "Please select a station.";
    } elseif ($startSql === null || $endSql === null) {
        $error = "Please provide a valid start and end date/time.";
    } elseif ($startSql > $endSql) {
        $error = "Start date/time must be before end date/time.";
    } else {
        // Security: ensure station belongs to the logged-in user
        $check = $conn->prepare("SELECT station_id FROM stations WHERE station_id = ? AND user_id = ?");
        $check->bind_param("ii", $station_id, $_SESSION['user_id']);
        $check->execute();
        $ok = $check->get_result()->num_rows === 1;

        if (!$ok) {
            $error = "You are not allowed to view measurements for this station.";
        } else {
            $q = $conn->prepare("
                SELECT measured_at, temperature, humidity, pressure, light, gas
                FROM measurements
                WHERE station_id = ?
                  AND measured_at BETWEEN ? AND ?
                ORDER BY measured_at DESC
                LIMIT 500
            ");
            $q->bind_param("iss", $station_id, $startSql, $endSql);
            $q->execute();
            $rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

require_once __DIR__ . "/../admin/includes/header.php";
?>

<h1 class="h3 mb-3">Measurements</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
  <h2 class="h5">Filter</h2>

  <form method="get" class="row g-3 align-items-end">
    <input type="hidden" name="filter" value="1">

    <div class="col-md-4">
      <label class="form-label">Station</label>
      <select class="form-select" name="station_id" required>
        <option value="0">-- Select station --</option>
        <?php foreach ($stations as $s): ?>
          <option value="<?= (int)$s['station_id'] ?>"
            <?= ((int)$s['station_id'] === $station_id) ? 'selected' : '' ?>>
            <?= e($s['name']) ?> (<?= e($s['serial_number']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Start</label>
      <input class="form-control" type="datetime-local" name="start_at" value="<?= e($start_at) ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">End</label>
      <input class="form-control" type="datetime-local" name="end_at" value="<?= e($end_at) ?>" required>
    </div>

    <div class="col-md-2">
      <button class="btn btn-dark w-100">Show</button>
    </div>
  </form>
</div>

<div class="card p-3">
  <h2 class="h5">Results</h2>

  <?php if (isset($_GET['filter']) && empty($error)): ?>
    <p class="text-muted">
      Showing up to 500 rows. Found: <?= count($rows) ?>.
    </p>

    <?php if (empty($rows)): ?>
      <p class="text-muted mb-0">No measurements in this range.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>Measured at</th>
              <th>Temp (°C)</th>
              <th>Humidity (%)</th>
              <th>Pressure (hPa)</th>
              <th>Light (lux)</th>
              <th>Gas (ppm)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= e($r['measured_at']) ?></td>
                <td><?= e((string)$r['temperature']) ?></td>
                <td><?= e((string)$r['humidity']) ?></td>
                <td><?= e((string)$r['pressure']) ?></td>
                <td><?= e((string)$r['light']) ?></td>
                <td><?= e((string)$r['gas']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-muted mb-0">Select a station and date/time range to view measurements.</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../admin/includes/footer.php"; ?>
