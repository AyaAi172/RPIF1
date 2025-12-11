<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session only if none exists
} // Start session management

// Step 1: Set the default language if not already set
if (!isset($_SESSION["language"])) {
    $_SESSION["language"] = "EN"; // Default language is English
}

// Step 2: Allow language switching via GET parameter
if (isset($_GET["language"])) {
    $_SESSION["language"] = $_GET["language"];
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "online_Cake_Project";

// Step 1: Create a connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Additional functions for user management
$registrationSuccessful = false;

// Step 3: Load translations from NavBarTranslation.csv
// Step 3: Load translations from SQL instead of CSV
$arrayOfStrings = []; // Array to store translations

$language = $_SESSION["language"];
$sql = "SELECT ID, English, French FROM Translations";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $arrayOfStrings[$row['ID']] = ($language == "EN") ? $row['English'] : $row['French'];
    }
} else {
    // Handle error or empty translations
    die("Failed to load translations.");
}
/*
$arrayOfStrings = []; // Array to store translations

$fileTranslations = fopen("../Database/NavBarTranslation.csv", "r");
$header = fgets($fileTranslations); // Skip the first row (header)

while (!feof($fileTranslations)) {
    $line = fgets($fileTranslations);
    $arrayOfPieces = explode(",", $line); // Split the line by commas
    if (count($arrayOfPieces) >= 3) { // Ensure the line has enough fields
        $arrayOfStrings[$arrayOfPieces[0]] = ($_SESSION["language"] == "EN")
            ? $arrayOfPieces[1] // English column
            : $arrayOfPieces[2]; // French column
    }
}
fclose($fileTranslations); // Close the file*/

// Step 4: Navigation bar function
function commoncodeNA($PageOpen)
{
    echo '<link rel="stylesheet" href="../Design/Cake.css">';
    global $arrayOfStrings;

    // Calculate total cart items
    $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
    <div class="NavAll">
        <div class="TopNav">
            <div class="MainLinks">
                <a href="../Pages/Home.php" <?php if ($PageOpen == "Home") {
                                                print("class='active'");
                                            } ?>><?php echo $arrayOfStrings["Home"]; ?></a>
                <a href="../Pages/About.php" <?php if ($PageOpen == "About") {
                                                    print("class='active'");
                                                } ?>><?php echo $arrayOfStrings["About"]; ?></a>
                <a href="../Pages/Products.php" <?php if ($PageOpen == "Products") {
                                                    print("class='active'");
                                                } ?>><?php echo $arrayOfStrings["Products"]; ?></a>
                <?php if (isset($_SESSION['username']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="../Pages/AddProduct.php" <?php if ($PageOpen == "AddProduct") {
                                                            print("class='active'");
                                                        } ?>><?php echo $arrayOfStrings["AddProduct"]; ?></a>
                    <a href="../Pages/Orders.php" <?php if ($PageOpen == "Orders") {
                                                        print("class='active'");
                                                    } ?>><?php echo $arrayOfStrings["Orders"]; ?></a>
                <?php endif; ?>

                <?php if (!isset($_SESSION['username'])): ?>
                    <a href="../Pages/Register.php" <?php if ($PageOpen == "Register") {
                                                        print("class='active'");
                                                    } ?>><?php echo $arrayOfStrings["Register"]; ?></a>
                    <a href="../Pages/Login.php" <?php if ($PageOpen == "Login") {
                                                        print("class='active'");
                                                    } ?>><?php echo $arrayOfStrings["Login"]; ?></a>
                <?php else: ?>
                    <a href="../Pages/Logout.php" style="margin-left: 10px;"><?php echo $arrayOfStrings["Logout"]; ?></a>
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="../Pages/ChangePassword.php"><?= $arrayOfStrings['Change Password']  ?></a>
                <?php endif; ?>

            </div>

            <div class="Icon">
                <?php if (isset($_SESSION['username']) && $_SESSION['role'] === 'customer'): ?>

                    <!-- Cart link with item count -->
                    <a href="../Pages/Cart.php" <?php if ($PageOpen == "Cart") {
                                                    print("class='active'");
                                                } ?>>
                        <?php echo $arrayOfStrings["Cart"]; ?> 🛒 (<?= $cartCount ?>)
                    </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <span style="margin-left: 10px;">
                        👤 <?php echo $arrayOfStrings["Welcome"] . " " . htmlspecialchars($_SESSION['username']); ?>
                    </span>
                <?php else: ?>
                    <span style="margin-left: 10px;">
                        👤 <?php echo $arrayOfStrings["Unknown"]; ?>
                    </span>
                <?php endif; ?>
                <div class="language-selector">
                    <form method="GET" style="margin: 0;">
                        <select name="language" onchange="this.form.submit()">
                            <option value="EN" <?php if ($_SESSION["language"] == "EN") echo "selected"; ?>>English</option>
                            <option value="FR" <?php if ($_SESSION["language"] == "FR") echo "selected"; ?>>French</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
}



function userExists($checkUser) // Check if the user already exists
{
    global $conn;
    $sqlSelect = $conn->prepare("SELECT * FROM Clients WHERE UserName = ? ");
    $sqlSelect->bind_param("s", $checkUser);
    $sqlSelect->execute();
    $result = $sqlSelect->get_result();
    if ($result->num_rows > 0) {
        return true;
    } else {
        return false;
    }
}

function passwordmatch($checkUser, $checkpassword) // Check if the password matches
{
    global $conn;
    $sqlSelect = $conn->prepare("SELECT * FROM Clients WHERE UserName = ? ");
    $sqlSelect->bind_param("s", $checkUser);
    $sqlSelect->execute();
    $result = $sqlSelect->get_result();
    if ($result->num_rows == 0) {
        return false;
    } else {
        $row = $result->fetch_assoc();
        //if ($row["Password"] == $checkpassword) {
        if (password_verify($checkpassword, $row["Password"])) {
            return true;
        }
    }
    return false;
}

function getUserRole($username) // Retrieve the role of a user
{
    global $conn;
    $sqlSelect = $conn->prepare("SELECT * FROM Clients WHERE UserName = ? ");
    $sqlSelect->bind_param("s", $username);
    $sqlSelect->execute();
    $result = $sqlSelect->get_result();
    if ($result->num_rows == 0) {
        return "customer"; // Default role
    } else {
        $row = $result->fetch_assoc();
        return $row["defaultRole"];
    }


    /*$fileUser = fopen("../Database/client.csv", "r");
    while (!feof($fileUser)) {
        $line = fgets($fileUser);
        $data = explode(";", $line);
        if ($data[0] === $username) {
            fclose($fileUser);
            return trim($data[2]); // Assuming the role is the third column
        }
    }
    fclose($fileUser);
    return "customer"; // Default role*/
}

/*function loginUser($username, $role) // Login the user
{
    $_SESSION['username'] = $username; // Set session username
    $_SESSION['role'] = $role;        // Set session role
}*/

function loginUser($username) // Login the user
{
    $_SESSION['username'] = $username; // Set session username
    $_SESSION['role'] = getUserRole($username); // Set session role
}

function logoutUser() // Logout the user
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_unset(); // Unset all session values
    session_destroy();  // Destroy the session
    header("Location: Home.php");
    exit();
}
?>