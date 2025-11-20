<?php

    $csv = file("/tmp/station/result.csv");
    if (!$csv) {
        $csv = [",,,,,"];
    }
    function csv($csv) {
	    return str_getcsv($csv, ",", "\"", "\\");
    }
    $rows = array_map('csv', $csv);
    //$header = array_shift($rows);
    $time = [];
    $temperature = [];
    $humidity = [];
    $pressure = [];
    $light = [];
    $gas = [];
    foreach ($rows as $data) {
        $time[] = substr($data[0], 0, 19); // Truncate nanoseconds, keep "YYYY-MM-DD HH:mm:ss"
        $light[] = $data[1];
        $temperature[] = $data[2];
        $pressure[] = $data[3];
        $humidity[] = $data[4];
        $gas[] = $data[5];
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>Current Weather</title>
    <style>
        body {
            font-family: sans-serif;
        }
        table {
            width: 400px;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: .5em;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-color: lightgray;
        }
    </style>
</head>
<body>
    <?php
    if (count($time) == 0) { ?>
        <p>No current data!</p>
    <?php } else { ?>
<table class="table">
    <tr>
        <th>Time</th>
        <td>
            <?= htmlspecialchars(end($time)) ?>
        </td>
    </tr>
    <tr>
        <th>Temperature</th>
        <td>
            <?= number_format((float)end($temperature), 2) ?> °C
        </td>
    </tr>
    <tr>
        <th>Humidity</th>
        <td>
            <?= number_format((float)end($humidity), 2) ?> %
        </td>
    </tr>
    <tr>
        <th>Pressure</th>
        <td>
            <?= number_format((float)end($pressure), 2) ?> hPa
        </td>
    </tr>
    <tr>
        <th>Light</th>
        <td>
            <?= number_format((float)end($light), 2) ?> lux
        </td>
    </tr>
    <tr>
        <th>Gas</th>
        <td>
            <?= number_format((float)end($gas), 2) ?> ppm
        </td>
    </tr>
    </table>
<?php } ?>
</body>
</html>
