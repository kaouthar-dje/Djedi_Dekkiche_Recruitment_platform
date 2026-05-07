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

$application_id = $data['application_id'] ?? '';
$interview_date = $data['interview_date'] ?? '';
$interviewer_id = $data['interviewer_id'] ?? '';

// Validate required fields
if (!$application_id || !$interview_date || !$interviewer_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Application ID, interview date, and interviewer are required']);
    exit;
}

// Validate interview date is in the future
$interview_datetime = new DateTime($interview_date);
$now = new DateTime();
if ($interview_datetime <= $now) {
    http_response_code(400);
    echo json_encode(['error' => 'Interview date must be in the future']);
    exit;
}

// Check if application exists
$sqlCheck = "SELECT application_id FROM application WHERE application_id = :id";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->execute([':id' => $application_id]);
$application = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    http_response_code(404);
    echo json_encode(['error' => 'Application not found']);
    exit;
}

// Check if application is in interviews stage or beyond
$stageStmt = $conn->prepare("SELECT stage FROM application_history WHERE app_id = :id ORDER BY changed_at DESC, application_history_id DESC LIMIT 1");
$stageStmt->execute([':id' => $application_id]);
$stageRow = $stageStmt->fetch(PDO::FETCH_ASSOC);
$currentStage = $stageRow['stage'] ?? 'screening';
$validStages = ['interviews', 'evaluation', 'decision'];
if (!in_array($currentStage, $validStages, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Application must be in interviews stage or beyond']);
    exit;
}

// Check if interview already exists
$sqlCheckInterview = "SELECT interview_id FROM interview WHERE appli_id = :application_id";
$stmtCheckInterview = $conn->prepare($sqlCheckInterview);
$stmtCheckInterview->execute([':application_id' => $application_id]);
if ($stmtCheckInterview->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Interview already scheduled for this application']);
    exit;
}

$recruiterCheck = $conn->prepare("SELECT user_id, role FROM `User` WHERE user_id = :id LIMIT 1");
$recruiterCheck->execute([':id' => $interviewer_id]);
$interviewer = $recruiterCheck->fetch(PDO::FETCH_ASSOC);

if (!$interviewer || $interviewer['role'] !== 'Recruiter') {
    http_response_code(400);
    echo json_encode(['error' => 'The interviewer must be a recruiter']);
    exit;
}

// Insert interview
$sql = "INSERT INTO interview (appli_id, interviewer_id, interview_date) 
        VALUES (:application_id, :interviewer_id, :interview_date)";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':application_id' => $application_id,
    ':interviewer_id' => $user_id,
    ':interview_date' => $interview_date
]);

$interviewId = $conn->lastInsertId();

echo json_encode([
    'message' => 'Interview scheduled successfully',
    'interview_id' => $interviewId,
    'application_id' => $application_id,
    'interview_date' => $interview_date
]);
?>
