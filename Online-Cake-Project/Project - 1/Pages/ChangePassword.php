<?php
include_once("../Database/CommonCode.php");
commoncodeNA("ChangePassword");

$message = "";

if (!isset($_SESSION['username'])) {
    header("Location: Login.php");
    exit();
}

if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $username = $_SESSION['username'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch current hashed password from DB
    $stmt = $conn->prepare("SELECT Password FROM Clients WHERE UserName = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($currentPassword, $user['Password'])) {
        $message = "Current password is incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
    } else {
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE Clients SET Password = ? WHERE UserName = ?");
        $updateStmt->bind_param("ss", $hashedPassword, $username);
        $updateStmt->execute();

        $message = "Password updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css">
    <title><?= $arrayOfStrings['Change Password']  ?></title>
</head>

<body>
    <h1 style="text-align:center;"> <?= $arrayOfStrings['Change Password']  ?></h1>
    <div class="form-container">
        <form method="POST">
            <div class="regesterform">
                <input type="password" name="current_password" placeholder="Current Password" required>
            </div>
            <div class="regesterform">
                <input type="password" name="new_password" placeholder="New Password" required>
            </div>
            <div class="regesterform">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <div class="regesterform">
                <button type="submit"><?= $arrayOfStrings['Submit']  ?></button>
            </div>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <p style="text-align:center; color: <?= strpos($message, 'successfully') ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>
</body>

</html>