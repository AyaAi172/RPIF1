<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css?= time() ?>">
    <title>Cake</title>
</head>

<body>
<?php
    include_once("../Database/CommonCode.php");
    commoncodeNA("Login");

    $feedbackMessage = ""; 

    if (isset($_POST["username"], $_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $sqlSelect = $conn->prepare("SELECT Password FROM Clients WHERE UserName = ?");
        $sqlSelect->bind_param("s", $username);
        $sqlSelect->execute();
        $result = $sqlSelect->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $row["Password"])) {
                loginUser($username); 

                header("Location: Home.php");
                exit();
            } else {
                $feedbackMessage = "Invalid username or password. Please try again.";
            }
        } else {
            
            $feedbackMessage = "Invalid username or password. Please try again.";
        }
    }
    ?>

    <?php if (!empty($feedbackMessage)): ?>
        <p style="color: red; text-align: center;"><?= htmlspecialchars($feedbackMessage) ?></p>
    <?php endif; ?>

    <!-- Login form -->
    <div class="form-container">
        <form method="POST">
            <div class="regesterform">
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
            <div class="regesterform">
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
           
            <div class="regesterform">
                <button type="submit"><?php echo $arrayOfStrings["Login"]; ?></button>
                
            </div>
        </form>
    </div>
</body>

</html>