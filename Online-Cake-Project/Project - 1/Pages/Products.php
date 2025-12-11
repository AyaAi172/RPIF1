<?php
include_once("../Database/CommonCode.php");

// Initialize the shopping cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle "Add to Cart" button
if (isset($_POST['addToCart'])) {
    $product = [
        'id' => $_POST['productID'],
        'name' => $_POST['productName'],
        'price' => (float) $_POST['productPrice'],
        'image' => $_POST['productImage'],
    ];

    $_SESSION['cart'][] = $product; // Add product to the cart
    header("Location: Products.php"); // Redirect to avoid form resubmission
    exit();
}

commoncodeNA("Products"); // Navigation and common functionality
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css">
    <title><?= htmlspecialchars($arrayOfStrings['Products']) ?> </title>
</head>

<body>
    <div class="AllProducts">
        <?php

        // SQL query to fetch all products from the database
        $sql = "SELECT * FROM products";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Output data for each product
            while ($row = $result->fetch_assoc()) {
                $productID = $row['ID'];
                $productName = ($_SESSION['language'] == "EN") ? $row['NameEN'] : $row['NameFR'];
                $productPrice = $row['Price'];
                $productImage = $row['Image'];
        ?>
                <div class="OneProduct">
                    <div class="ProductName"><?= $productName ?></div>
                    <div class="ImageContainer">
                        <img src="../Database/Images/<?= $productImage ?>" class="ProductImage" alt="<?= $productName ?>">
                    </div>
                    <div class="Price"><?= $productPrice ?> €</div>

                    <?php if (isset($_SESSION['username'])): ?>
                        <form method="POST" action="Products.php">
                            <input type="hidden" name="productID" value="<?= htmlspecialchars($productID) ?>">
                            <input type="hidden" name="productName" value="<?= htmlspecialchars($productName) ?>">
                            <input type="hidden" name="productPrice" value="<?= htmlspecialchars($productPrice) ?>">
                            <input type="hidden" name="productImage" value="<?= htmlspecialchars($productImage) ?>">
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
                                <button type="submit" name="addToCart" class="add-to-cart">
                                    <?= $arrayOfStrings["Add to cart"] ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <p style="color: gray; font-size: 14px; text-align: center;"><?php echo $arrayOfStrings["Login to add products to your cart"]; ?></p>
                    <?php endif; ?>
                </div>
        <?php
            }
        }
        ?>
    </div>
</body>

</html>