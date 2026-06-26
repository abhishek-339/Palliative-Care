<?php
include '../mysql_db.php';

header('Content-Type: application/json');

if (isset($_GET['query'])) {
    $searchTerm = "%" . $_GET['query'] . "%";

    // Prepare the query to search patients by name
    $stmt = $conn->prepare("SELECT user_id, name, phone, email FROM patient WHERE name LIKE ?");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }

    // Return the results as JSON
    echo json_encode($patients);
} else {
    echo json_encode([]);
}
?>