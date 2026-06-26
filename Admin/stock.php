<?php
// Database connection using mysqli
include '../mysql_db.php';

// Fetch all stock items using mysqli
function fetchAllStock($conn) {
    $stmt = $conn->prepare("SELECT * FROM stock");
    $stmt->execute();
    $result = $stmt->get_result(); // Get result set
    return $result->fetch_all(MYSQLI_ASSOC); // Fetch all results as associative array
}

// Search items by name
function searchStock($conn, $term) {
    $stmt = $conn->prepare("SELECT * FROM stock WHERE name LIKE ?");
    $searchTerm = "%" . $term . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC); // Fetch matching items
}

// Handle adding/updating items
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Add new item
        if (isset($_POST['add_item'])) {
            $name = htmlspecialchars(trim($_POST['name']));
            $description = htmlspecialchars(trim($_POST['description']));
            $quantity = (int)$_POST['quantity'];
            $price = (float)$_POST['price'];
            $barcode = htmlspecialchars(trim($_POST['barcode']));

            $stmt = $conn->prepare("INSERT INTO stock (name, description, quantity, price, barcode) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssids", $name, $description, $quantity, $price, $barcode);
            $stmt->execute();
            $message = 'Item added successfully!';
        }

        // Update stock quantity
        elseif (isset($_POST['update_stock'])) {
            $id = (int)$_POST['id'];
            $quantity = (int)$_POST['quantity'];

            $stmt = $conn->prepare("UPDATE stock SET quantity = quantity + ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity, $id);
            $stmt->execute();
            $message = 'Stock updated successfully!';
        }

        // Delete item
        elseif (isset($_POST['delete_item'])) {
            $id = (int)$_POST['id'];
            $quantity = (int)$_POST['quantity'];

            // If quantity is greater than 0, reduce stock, else delete item
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE stock SET quantity = quantity - ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $id);
                $stmt->execute();
                $message = 'Stock quantity reduced successfully!';
            } else {
                $stmt = $conn->prepare("DELETE FROM stock WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $message = 'Item deleted successfully!';
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

$stockItems = fetchAllStock($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Inventory and Stock Management</h1>

    <!-- Display success or error message -->
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" onclick="window.location.href=window.location.href;">Refresh</button>


    <!-- Add Item Button -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addItemModal">Add Item/Stock</button>

    <!-- Search Box with Dynamic Suggestions -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Search items..." onkeyup="searchItems()">
        <ul id="suggestions" class="list-group mt-2" style="display: none;"></ul>
    </div>

    <!-- Stock Table -->
    <table class="table table-bordered" id="stockTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Barcode</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stockItems as $item): ?>
                <tr data-item-name="<?= strtolower($item['name']) ?>">
                    <td><?= htmlspecialchars($item['id']) ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['price']) ?></td>
                    <td><?= htmlspecialchars($item['barcode']) ?></td>
                    <td>
                        <!-- Update Stock Modal Trigger -->
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateStockModal" data-id="<?= $item['id'] ?>" data-quantity="<?= $item['quantity'] ?>">Update Quantity</button>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteItemModal" data-id="<?= $item['id'] ?>" data-quantity="<?= $item['quantity'] ?>">Check Out</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Add New Item/Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="text" class="form-control" name="barcode">
                    </div>
                    <button type="submit" name="add_item" class="btn btn-success">Add Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStockModalLabel">Update Stock Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="id" id="updateStockId">
                    <div class="mb-3">
                        <label for="updateQuantity" class="form-label">Quantity to Add</label>
                        <input type="number" class="form-control" name="quantity" id="updateQuantity" placeholder="0"required>
                    </div>
                    <button type="submit" name="update_stock" class="btn btn-warning">Update Quantity</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Item Modal -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteItemModalLabel">Delete Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item or reduce stock?</p>
                <form method="post">
                    <input type="hidden" name="id" id="deleteItemId">
                    <input type="number" name="quantity" id="deleteQuantity" min="1" required>
                    <button type="submit" name="delete_item" class="btn btn-danger">Check Out</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle modal population with the current stock data for Update Stock
    var updateStockModal = document.getElementById('updateStockModal');
    updateStockModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var quantity = button.getAttribute('data-quantity');
        document.getElementById('updateStockId').value = id;
        //document.getElementById('updateQuantity').value = quantity;
    });

    // Handle modal population for Delete Item
    var deleteItemModal = document.getElementById('deleteItemModal');
    deleteItemModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        document.getElementById('deleteItemId').value = id;
    });

    // Dynamic search for items
    function searchItems() {
        var query = document.getElementById('searchInput').value.toLowerCase();
        var rows = document.querySelectorAll('#stockTable tbody tr');
        rows.forEach(row => {
            var itemName = row.getAttribute('data-item-name');
            if (itemName.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
