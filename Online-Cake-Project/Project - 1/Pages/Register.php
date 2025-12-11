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
    commoncodeNA("Register");

    $feedbackMessage = "";
    
    // this code is for the registration form to check if the user entered all the information
    if (isset($_POST["username"], $_POST["password"], $_POST["confirmpassword"], $_POST["Email"], $_POST["Address"], $_POST["Phone"])) {
        if (empty($_POST["username"]) || empty($_POST["password"]) || empty($_POST["confirmpassword"] || empty($_POST["Email"]) || empty($_POST["Address"]) || empty($_POST["Phone"]))) {
            $feedbackMessage = "All fields are required. Please fill them out:)";
        } elseif ($_POST["password"] !== $_POST["confirmpassword"]) {
            $feedbackMessage = "Passwords do not match. Please check and try again:)";
        } elseif (userExists($_POST["username"])) { // this function is to check if the user already exists
            $feedbackMessage = "Username already exists. Please choose another username:)";
        } else {
            //we will register the user to our database online_cake_project
            $defaultRole = "customer";
            $hashedPassword = password_hash($_POST["password"], PASSWORD_DEFAULT);
            $sqlInsert =  $conn->prepare(" Insert into clients (username, password, Email, Address, Phone, defaultRole) values (?, ?, ?, ?, ?,?)");
            $sqlInsert->bind_param("ssssss", $_POST["username"], $hashedPassword, $_POST["Email"], $_POST["Address"], $_POST["Phone"], $defaultRole);
            $sqlInsert->execute();
            $feedbackMessage = "Registration successful. Please login:)";
        }
    }
    ?>

    <div class="form-container">
        <form method="POST">

            <div class="regesterform">
                <input type="text" name="username" placeholder="choose a username">
            </div>
            <div class="regesterform">
                <input type="password" name="password" placeholder="choose a password">
            </div>

            <div class="regesterform">
                <input type="password" name="confirmpassword" placeholder="confirm password">
            </div>
            
            <div class="regesterform" >
                <input type="text" name="Email" placeholder="Write your Email"  >
            </div>

            <div class="regesterform">
                <input type="text" name="Address" placeholder="Write your Address">
            </div>

            <div class="regesterform">
                <input type="text" name="Phone" placeholder="Write your Phone Number">
            </div>
            <div class="regesterform">
                <button type="submit"><?php echo $arrayOfStrings["Submit"]; ?></button>
            </div>

        </form>
    </div>
    <?php if (!empty($feedbackMessage)): ?>
        <p style="color: red; text-align: center;"><?= htmlspecialchars($feedbackMessage) ?></p>
    <?php endif; ?>

</body>

</html>