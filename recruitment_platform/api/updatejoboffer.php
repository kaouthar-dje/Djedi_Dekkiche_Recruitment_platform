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
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $minexperience = (int)($_POST['minexperience'] ?? 0);
    $numberofpositions = (int)($_POST['numberofpositions'] ?? 0);
    $enddate = $_POST['enddate'] ?? null;
    
    if (!$id || !$title || !$department) {
        http_response_code(400);
        echo json_encode(['error' => 'Job ID, title, and department are required']);
        exit;
    }

    $sql = "UPDATE joboffer SET title = :title, department = :department, minexperience = :minexperience, numberofpositions = :numberofpositions, enddate = :enddate WHERE job_id = :job_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':job_id' => $id,
        ':title' => $title,
        ':department' => $department,
        ':minexperience' => $minexperience,
        ':numberofpositions' => $numberofpositions,
        ':enddate' => $enddate
    ]);

    echo json_encode(['message' => 'Job updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
