<?php
include_once("../Database/CommonCode.php");
commoncodeNA("Orders");

// Restrict access to admin users only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Home.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $orderID = $_POST['order_id'];
    $newStatus = $_POST['status'];

    $stmt = $conn->prepare("UPDATE Orders SET Status = ? WHERE OrderID = ?");
    $stmt->bind_param("si", $newStatus, $orderID);
    $stmt->execute();
}

// Fetch all orders with product details
$sql = "
SELECT o.OrderID, c.UserName, o.OrderDate, o.TotalPrice, o.Status,
       p.ID,
       IF('{$_SESSION['language']}' = 'FR', p.NameFR, p.NameEN) AS ProductName,
       oi.Price
FROM Orders o
JOIN Clients c ON o.ClientID = c.ClientId
JOIN OrderItems oi ON o.OrderID = oi.OrderID
JOIN Products p ON oi.ProductID = p.ID
ORDER BY o.OrderDate DESC

";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['language'] === "FR" ? 'fr' : 'en' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Design/Cake.css">
    <title><?= $arrayOfStrings['All Orders'] ?></title>
</head>

<body>
    <h1 style="text-align: center;"><?= $arrayOfStrings['All Orders'] ?></h1>
    <div class="AllOrders">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= $arrayOfStrings['Username'] ?></th>
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
                    while ($row = $result->fetch_assoc()) {
                        $orders[$row['OrderID']][] = $row;
                    }

                    foreach ($orders as $orderID => $items):
                        $rowspan = count($items);
                        $first = true;
                        foreach ($items as $item):
                    ?>
                            <tr>
                                <?php if ($first): ?>
                                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($items[0]['UserName']) ?></td>
                                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($items[0]['OrderDate']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($item['ProductName']) ?></td>
                                <td><?= htmlspecialchars($item['Price']) ?> €</td>
                                <?php if ($first): ?>
                                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($items[0]['TotalPrice']) ?> €</td>
                                    <?php if ($first): ?>
                                        <td rowspan="<?= $rowspan ?>">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?= $items[0]['OrderID'] ?>">
                                                <select name="status" onchange="this.form.submit()"
                                                    style="color: white; padding: 5px; border: none; border-radius: 5px; 
            background-color: <?= ($items[0]['Status'] == 'Delivered') ? 'green' : 'red' ?>;">
                                                    <option value="Pending" <?= ($items[0]['Status'] == 'Pending') ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($arrayOfStrings['Pending']) ?>
                                                    </option>
                                                    <option value="Delivered" <?= ($items[0]['Status'] == 'Delivered') ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($arrayOfStrings['Delivered']) ?>
                                                    </option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                    <?php endif; ?>


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
            <?php else: ?>
                <p style="text-align: center;"><?= $arrayOfStrings['No orders found'] ?></p>
            <?php endif; ?>
    </div>
</body>

</html>