<?php
include_once("../Database/CommonCode.php");
commoncodeNA("About");
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['language'] === "FR" ) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css?= time() ?>">
    <title><?= htmlspecialchars($arrayOfStrings['ABOUT US'] ) ?></title>
</head>

<body>
    <div class="hero-section">
        <div class="content-box">
            <h1><?php echo $arrayOfStrings["ABOUT US"]; ?></h1>
            <p>
                <?php echo $arrayOfStrings["aboutUs"]; ?>
            </p>
        </div>
    </div>
</body>

</html>
