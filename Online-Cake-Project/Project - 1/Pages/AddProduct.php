<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css?= time() ?>">
    <title>Add Product</title>
</head>

<body>
    <?php
    include_once("../Database/CommonCode.php");
    commoncodeNA("AddProduct");

    $feedbackMessage = "";

    // Check if the user is an admin
    if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
        // Redirect to Home if not an admin
        header("Location: Home.php");
        exit();
    }


    // Handle form submission
    if (isset($_POST['productNameEN'], $_POST['productNameFR'], $_POST['productPrice'], $_POST['productImage'])) {
        $productNameEN = $_POST['productNameEN']; // Product name in English
        $productNameFR = $_POST['productNameFR']; // Product name in French
        $productPrice = $_POST['productPrice'];
        $productImage = $_POST['productImage'];


        // Insert the new product into the database
        $sqlInsert = $conn->prepare("INSERT INTO Products (NameEN, Price, Image, NameFR) VALUES (?, ?, ?, ?)");
        $sqlInsert->bind_param("siss", $productNameEN,  $productPrice,$productImage,$productNameFR);
        
        if ($sqlInsert->execute()) {
            $feedbackMessage = "Product added successfully!";
        } else {
            $feedbackMessage = "Failed to add the product. Please try again.";
        }

    }
    ?>

    <!-- Display feedback message if available -->
    <?php if (!empty($feedbackMessage)): ?>
        <p style="color: green; text-align: center;"><?= htmlspecialchars($feedbackMessage) ?></p>
    <?php endif; ?>

    <!-- Add Product Form -->
    <div class="form-container">
        <form method="POST">
            <div class="regesterform">
                <input type="text" name="productNameEN" placeholder="Product Name (English)" required>
            </div>
            <div class="regesterform">
                <input type="text" name="productNameFR" placeholder="Product Name (French)" required>
            </div>
            <div class="regesterform">
                <input type="number" name="productPrice" placeholder="Product Price" required>
            </div>
            <div class="regesterform">
                <input type="text" name="productImage" placeholder="Product Image Path (e.g., images/product.jpg)" required>
            </div>
            <div class="regesterform">
                <button type="submit"><?php echo $arrayOfStrings["AddProduct"]; ?></button>
            </div>
        </form>
    </div>
</body>

</html>