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
    
    $user_role = $data['user_role'] ?? null;
    $id = isset($data['id']) ? (int)$data['id'] : null;
    
    // Authorization: Only recruiters can eliminate candidates
    if ($user_role !== 'Recruiter') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized Action: Only recruiters can eliminate candidates']);
        exit;
    }

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Application ID is required']);
        exit;
    }

    $sql = "UPDATE application SET status = 'eliminated' WHERE application_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Candidate eliminated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
