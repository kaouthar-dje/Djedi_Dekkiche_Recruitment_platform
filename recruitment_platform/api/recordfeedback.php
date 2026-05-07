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

$data = json_decode(file_get_contents("php://input"), true);

// Get user info
$user_id = $data['user_id'] ?? '';
$user_role = $data['user_role'] ?? '';

checkAuthorization(['HR', 'Recruiter']);

$interview_id = $data['interview_id'] ?? '';
$feedback = $data['feedback'] ?? '';
$rating = $data['rating'] ?? null;

// Validate required fields
if (!$interview_id || !$feedback) {
    http_response_code(400);
    echo json_encode(['error' => 'Interview ID and feedback are required']);
    exit;
}

// Validate rating if provided
if ($rating !== null && ($rating < 1 || $rating > 5)) {
    http_response_code(400);
    echo json_encode(['error' => 'Rating must be between 1 and 5']);
    exit;
}

// Check if interview exists
$sqlCheck = "SELECT interview_id, appli_id FROM interview WHERE interview_id = :id AND interviewer_id = :interviewer_id";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->execute([
    ':id' => $interview_id,
    ':interviewer_id' => $user_id
]);
$interview = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$interview) {
    http_response_code(404);
    echo json_encode(['error' => 'Interview not found or you are not the interviewer']);
    exit;
}

// Update interview with feedback
$sql = "UPDATE interview SET feedback = :feedback, rating = :rating WHERE interview_id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':feedback' => $feedback,
    ':rating' => $rating,
    ':id' => $interview_id
]);

echo json_encode([
    'message' => 'Feedback recorded successfully',
    'interview_id' => $interview_id,
    'application_id' => $interview['appli_id']
]);
?>
