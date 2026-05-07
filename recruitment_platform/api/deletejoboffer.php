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
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? ($data['jobId'] ?? null);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Job ID is required']);
        exit;
    }

    $sql = "DELETE FROM joboffer WHERE job_id = :jobId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':jobId', $id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['message' => 'Job deleted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
