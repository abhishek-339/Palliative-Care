<?php
// Database connection using mysqli
include '../mysql_db.php';

// Fetch all patient equipment deployments
function fetchAllPatientEquipment($conn)
{
    $stmt = $conn->prepare("SELECT pe.*, p.name AS patient_name, p.address AS patient_address, p.phone AS patient_phone, p.email AS patient_email
                            FROM patient_equipment pe
                            JOIN patient p ON pe.patient_id = p.user_id");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Search for patients
function searchPatients($conn, $term)
{
    $stmt = $conn->prepare("SELECT * FROM patient WHERE name LIKE ?");
    $searchTerm = "%" . $term . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle adding/updating entries
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Add new entry
        if (isset($_POST['add_entry'])) {
            $patient_id = (int) $_POST['patient_id'];
            $item_name = htmlspecialchars(trim($_POST['item_name']));
            $quantity = (int) $_POST['quantity'];
            $date_given = htmlspecialchars(trim($_POST['date_given']));

            $stmt = $conn->prepare("INSERT INTO patient_equipment (patient_id, item_name, quantity, date_given) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $patient_id, $item_name, $quantity, $date_given);
            $stmt->execute();
            $message = 'Equipment given out successfully!';
        }

        // Update existing entry (add more or return equipment)
        elseif (isset($_POST['update_entry'])) {
            $id = (int) $_POST['id'];
            $quantity = (int) $_POST['quantity'];

            $stmt = $conn->prepare("UPDATE patient_equipment SET quantity = quantity + ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity, $id);
            $stmt->execute();
            $message = 'Equipment quantity updated successfully!';
        }

        // Delete item (equipment returned)
        elseif (isset($_POST['delete_entry'])) {
            $id = (int) $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM patient_equipment WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $message = 'Equipment returned and entry deleted successfully!';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// Fetch all patient equipment entries
$patientEquipment = fetchAllPatientEquipment($conn);

// Handle patient search if search term is given
$patients = [];
if (isset($_GET['query'])) {
    $patients = searchPatients($conn, $_GET['query']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Equipment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

</head>

<body>
    <div class="container mt-4">
        <h1>Patient Equipment Management</h1>

        <!-- Display success or error message -->
        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" onclick="window.location.href=window.location.href;">Refresh</button>

        <!-- Add New Equipment Entry Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEntryModal">Add New Equipment
            Detail</button>

        <!-- Search Box with Dynamic Suggestions -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search patients..."
                onkeyup="searchItems()">
            <ul id="suggestions" class="list-group mt-2" style="display: none;"></ul>
        </div>

        <!-- Patient Equipment Table -->
        <table class="table table-bordered" id="patientEquipmentTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Item Given Out</th>
                    <th>Quantity</th>
                    <th>Date Given</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patientEquipment as $entry): ?>
                    <tr data-item-name="<?= strtolower($entry['item_name']) ?>">
                        <td><?= htmlspecialchars($entry['id']) ?></td>
                        <td><?= htmlspecialchars($entry['patient_name']) ?></td>
                        <td><?= htmlspecialchars($entry['patient_address']) ?></td>
                        <td><?= htmlspecialchars($entry['patient_phone']) ?></td>
                        <td><?= htmlspecialchars($entry['patient_email']) ?></td>
                        <td><?= htmlspecialchars($entry['item_name']) ?></td>
                        <td><?= htmlspecialchars($entry['quantity']) ?></td>
                        <td><?= htmlspecialchars($entry['date_given']) ?></td>
                        <td>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateEntryModal"
                                data-id="<?= $entry['id'] ?>" data-quantity="<?= $entry['quantity'] ?>">Update</button>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteEntryModal"
                                data-id="<?= $entry['id'] ?>" data-quantity="<?= $entry['quantity'] ?>">Return</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Equipment Entry Modal -->
    <div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="addEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEntryModalLabel">Add New Equipment Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient ID</label>
                            <div class="d-flex">
                                <input type="number" class="form-control" name="patient_id" id="patient_id" required
                                    placeholder="Enter patient ID">

                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="patient_name" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="patient_name" name="patient_name"
                                placeholder="Search for patient by name">
                            <ul id="patient_search_results" class="list-group" style="display: none;"></ul>
                        </div>
                        <div class="mb-3">
                            <label for="patient_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="patient_phone" name="patient_phone" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="patient_email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="patient_email" name="patient_email" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="item_name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="item_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_given" class="form-label">Date Given</label>
                            <input type="date" class="form-control" name="date_given" required>
                        </div>
                        <button type="submit" name="add_entry" class="btn btn-success">Add Entry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Search Patient Modal -->
    <div class="modal fade" id="searchPatientModal" tabindex="-1" aria-labelledby="searchPatientModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchPatientModalLabel">Search for Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="patient_search" class="form-label">Enter Patient Name</label>
                        <input type="text" id="patient_search" class="form-control"
                            placeholder="Enter name to search...">
                        <ul id="patient_search_results" class="list-group mt-3" style="display: none;"></ul>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Update Equipment Modal -->
    <div class="modal fade" id="updateEntryModal" tabindex="-1" aria-labelledby="updateEntryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateEntryModalLabel">Update Equipment Quantity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="id" id="updateEntryId">
                        <div class="mb-3">
                            <label for="updateQuantity" class="form-label">Quantity to Add/Return</label>
                            <input type="number" class="form-control" name="quantity" id="updateQuantity" required>
                        </div>
                        <button type="submit" name="update_entry" class="btn btn-warning">Update Quantity</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Equipment Modal -->
    <div class="modal fade" id="deleteEntryModal" tabindex="-1" aria-labelledby="deleteEntryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEntryModalLabel">Return Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to return this equipment?</p>
                    <form method="post">
                        <input type="hidden" name="id" id="deleteEntryId">
                        <button type="submit" name="delete_entry" class="btn btn-danger">Return</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle modal population with the current data for Update Entry
        var updateEntryModal = document.getElementById('updateEntryModal');
        updateEntryModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var quantity = button.getAttribute('data-quantity');
            document.getElementById('updateEntryId').value = id;
            document.getElementById('updateQuantity').value = 0;
        });

        // Handle modal population for Delete Entry
        var deleteEntryModal = document.getElementById('deleteEntryModal');
        deleteEntryModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            document.getElementById('deleteEntryId').value = id;
            document.getElementById('quantity').value = quantity;
        });

        // Dynamic search for items
        function searchItems() {
            var query = document.getElementById('searchInput').value.toLowerCase();
            var rows = document.querySelectorAll('#patientEquipmentTable tbody tr');
            rows.forEach(row => {
                var patientName = row.cells[1].textContent.toLowerCase();  // Patient Name column
                var patientAddress = row.cells[2].textContent.toLowerCase();  // Address column
                var patientPhone = row.cells[3].textContent.toLowerCase();  // Phone column
                var patientEmail = row.cells[4].textContent.toLowerCase();  // Email column
                var itemName = row.getAttribute('data-item-name').toLowerCase();  // Item Name

                // Check if query matches any of the columns
                if (patientName.includes(query) || patientAddress.includes(query) || patientPhone.includes(query) || patientEmail.includes(query) || itemName.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Search for patients dynamically as user types
        document.getElementById('patient_name').addEventListener('keyup', function () {
            var query = this.value.toLowerCase();
            if (query.length > 0) {
                fetchPatients(query);
            } else {
                document.getElementById('patient_search_results').style.display = 'none';
            }
        });

        function fetchPatients(query) {
            // Make an AJAX request to the server to search for patients
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "search_patients.php?query=" + encodeURIComponent(query), true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var patients = JSON.parse(xhr.responseText);
                    displayPatients(patients);
                }
            };
            xhr.send();
        }

        function displayPatients(patients) {
            var resultList = document.getElementById('patient_search_results');
            resultList.innerHTML = ''; // Clear previous results

            if (patients.length === 0) {
                resultList.style.display = 'none';
                console.log('No results found');
            } else {
                resultList.style.display = 'block';
                patients.forEach(function (patient) {
                    var listItem = document.createElement('li');
                    listItem.classList.add('list-group-item', 'list-group-item-action');
                    listItem.innerHTML = `${patient.name} (ID: ${patient.user_id}, Phone: ${patient.phone}, Email: ${patient.email})`;
                    listItem.addEventListener('click', function () {
                        selectPatient(patient);
                    });
                    resultList.appendChild(listItem);
                });
            }
        }

        function selectPatient(patient) {
            document.getElementById('patient_id').value = patient.user_id;
            document.getElementById('patient_name').value = patient.name;
            document.getElementById('patient_phone').value = patient.phone;
            document.getElementById('patient_email').value = patient.email;

            // Hide the search results
            document.getElementById('patient_search_results').style.display = 'none';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>