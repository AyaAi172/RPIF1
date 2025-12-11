<?php
include_once("../Database/CommonCode.php");
commoncodeNA("Home");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css?= time() ?>">
    <title><?= htmlspecialchars($arrayOfStrings['Home']) ?></title>
</head>

<body>
    <div class="hero-section">
        <div class="content-box">
            <h1><?= htmlspecialchars($arrayOfStrings['WELCOME TO THE WORLD OF CAKES']) ?></h1>
            <p><?= htmlspecialchars($arrayOfStrings['Perfect for every celebration.']) ?></p>
            <a href="Products.php" class="btn"><?= htmlspecialchars($arrayOfStrings['Explore Our Cakes']) ?></a>
        </div>
    </div>
</body>
</html>
