<?php
include '../mysql_db.php';

$sql = "SELECT id, name, description, quantity, price FROM stock";
$result = $conn->query($sql);

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>