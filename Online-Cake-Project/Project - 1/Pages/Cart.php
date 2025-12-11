<?php
include_once("../Database/CommonCode.php");
commoncodeNA("Cart");

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Clear cart
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    header("Location: Cart.php");
    exit();
}

// Finalize order
if (isset($_POST['finalize_order']) && !empty($_SESSION['cart'])) {
    $username = mysqli_real_escape_string($conn, $_SESSION['username']);

    $resultClient = mysqli_query($conn, "SELECT ClientId FROM Clients WHERE UserName = '$username'");
    $client = mysqli_fetch_assoc($resultClient);

    if (!$client) {
        die("Error: Client not found.");
    }

    $clientID = $client['ClientId'];
    $totalPrice = array_sum(array_column($_SESSION['cart'], 'price'));
    mysqli_query($conn, "INSERT INTO Orders (ClientID, OrderDate, TotalPrice, Status) VALUES ('$clientID', NOW(), '$totalPrice', 'Pending')");
    $orderID = mysqli_insert_id($conn);

    foreach ($_SESSION['cart'] as $item) {
        $productID = (int)$item['id'];
        $price = $item['price'];
        mysqli_query($conn, "INSERT INTO OrderItems (OrderID, ProductID, Quantity, Price) VALUES ($orderID, $productID, 1, $price)");
    }

    $_SESSION['cart'] = [];
    header("Location: Cart.php?success=1");
    exit();
}

// Remove item
if (isset($_POST['remove_item'])) {
    $index = $_POST['remove_item'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header("Location: Cart.php");
    exit();
}

// Fetch user's order history
$username = mysqli_real_escape_string($conn, $_SESSION['username']);
$orderHistory = mysqli_query($conn, "
   SELECT o.OrderID, o.OrderDate, o.TotalPrice, o.Status, 
          p.ID,
          IF('{$_SESSION['language']}' = 'FR', p.NameFR, p.NameEN) AS ProductName,
          oi.Price
   FROM Orders o
   JOIN Clients c ON o.ClientID = c.ClientId
   JOIN OrderItems oi ON o.OrderID = oi.OrderID
   JOIN Products p ON oi.ProductID = p.ID
   WHERE c.UserName = '$username'
   ORDER BY o.OrderDate DESC
");

// Fetch product info
$productInfo = [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $productInfo[$item['id']] = [
            'name' => $item['name'],
            'price' => $item['price']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['language'] === "FR" ? 'fr' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($arrayOfStrings['Cart']) ?></title>
    <link rel="stylesheet" href="../Design/Cake.css">
</head>
<body>
    <h1 style="text-align: center;"><?= $arrayOfStrings['Your Shopping Cart'] ?></h1>
    <div class="AllProducts">
        <?php if (!empty($_SESSION['cart'])): ?>
            <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                <div class="OneProduct">
                    <div class="ProductName"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="ImageContainer">
                        <img src="../Database/Images/<?= htmlspecialchars($item['image']) ?>" class="ProductImage">
                    </div>
                    <div class="Price"><?= htmlspecialchars($item['price']) ?> €</div>
                    <form method="POST">
                        <button type="submit" name="remove_item" value="<?= $index ?>" class="REMOVE">
                            <?= $arrayOfStrings['Remove'] ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
            <div style="text-align: center; margin-top: 20px;">
                <p><?= $arrayOfStrings['Total'] ?>: <strong><?= array_sum(array_column($_SESSION['cart'], 'price')) ?> €</strong></p>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="finalize_order" class="ADD"><?= $arrayOfStrings['Finalize Order'] ?></button>
                </form>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_cart" class="REMOVE"><?= $arrayOfStrings['Clear Cart'] ?></button>
                </form>
            </div>
        <?php else: ?>
            <p style="text-align: center; font-size: 18px;"><?= $arrayOfStrings['Your cart is empty'] ?></p>
        <?php endif; ?>
    </div>

    <h2 style="text-align: center;"><?= $arrayOfStrings['Your Order History'] ?></h2>
    <table>
        <thead>
            <tr>
                <th><?= $arrayOfStrings['Order Date'] ?></th>
                <th><?= $arrayOfStrings['Product Name'] ?></th>
                <th><?= $arrayOfStrings['Price'] ?></th>
                <th><?= $arrayOfStrings['Total Price'] ?></th>
                <th><?= $arrayOfStrings['Status'] ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $orders = [];
        while ($row = mysqli_fetch_assoc($orderHistory)) {
            $orders[$row['OrderID']][] = $row;
        }

        foreach ($orders as $orderID => $items):
            $rowspan = count($items);
            $first = true;
            foreach ($items as $item):
        ?>
            <tr>
                <?php if ($first): ?>
                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($item['OrderDate']) ?></td>
                <?php endif; ?>
                <td><?= htmlspecialchars($item['ProductName']) ?></td>
                <td><?= htmlspecialchars($item['Price']) ?> €</td>
                <?php if ($first): ?>
                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($item['TotalPrice']) ?> €</td>
                    <td rowspan="<?= $rowspan ?>">
                        <span style="padding: 5px; color: white; border-radius: 5px; background-color: <?= ($item['Status'] == 'Pending') ? 'red' : 'green' ?>;">
                            <?= htmlspecialchars($item['Status']) ?>
                        </span>
                    </td>
                <?php 
                    $first = false;
                endif; 
                ?>
            </tr>
        <?php 
            endforeach;
        endforeach;
        ?>
        </tbody>
    </table>
</body>
</html>
