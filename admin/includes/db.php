<?php
// db.php: connect to MySQL (XAMPP)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pif_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
