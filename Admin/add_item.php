<?php
include '../mysql_db.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];
$description = $data['description'];
$quantity = $data['quantity'];
$price = $data['price'];
$barcode = $data['barcode'];

// Check if the item already exists
$sql = "SELECT id FROM stock WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing item
    $sql = "UPDATE stock SET quantity = quantity + ?, price = ? WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ids', $quantity, $price, $name);
} else {
    // Insert new item
    $sql = "INSERT INTO stock (name, description, quantity, price, barcode) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssids', $name, $description, $quantity, $price, $barcode);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>