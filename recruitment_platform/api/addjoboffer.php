<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $title = trim($_POST['title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $minexperience = (int)($_POST['minexperience'] ?? 0);
    $numberofpositions = (int)($_POST['numberofpositions'] ?? 0);
    $enddate = $_POST['enddate'] ?? null;
    
    if (!$title || !$department || !$numberofpositions || $enddate === null || $enddate === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Title, department, positions, and end date are required']);
        exit;
    }

    $sql = "INSERT INTO joboffer (title, department, minexperience, numberofpositions, enddate) VALUES (:title, :department, :minexperience, :numberofpositions, :enddate)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':department' => $department,
        ':minexperience' => $minexperience,
        ':numberofpositions' => $numberofpositions,
        ':enddate' => $enddate
    ]);

    echo json_encode(['message' => 'Job posted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
