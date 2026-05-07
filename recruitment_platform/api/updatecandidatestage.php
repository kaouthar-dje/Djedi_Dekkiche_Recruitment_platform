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
    // receive data from frontend 
    $data = json_decode(file_get_contents("php://input"), true);
    
    $user_role = $data['user_role'] ?? null;
    
    // Authorization: Only recruiters can update candidate stage
    if ($user_role !== 'Recruiter') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Only recruiters can update candidate stage']);
        exit;
    }
    
    $id = $data['id'];
    $nextStage = $data['stage'];

    // allowed stage transitions
    $allowedTransitions = [
        "screening" => ["interviews"],
        "interviews" => ["evaluation"],
        "evaluation" => ["decision"],
        "decision" => []  // Final stage
    ];
    $sqlCurrent = "SELECT stage
                   FROM application_history
                   WHERE app_id = :id
                   ORDER BY changed_at DESC, application_history_id DESC
                   LIMIT 1";
    $stmtCurrent = $conn->prepare($sqlCurrent);
    $stmtCurrent->execute([':id' => $id]);
    $result = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
    $currentStage = $result['stage'] ?? 'screening';

    $appCheck = $conn->prepare("SELECT application_id FROM application WHERE application_id = :id");
    $appCheck->execute([':id' => $id]);
    if (!$appCheck->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
        exit;
    }

    // Validate transition
    if (!isset($allowedTransitions[$currentStage]) || !in_array($nextStage, $allowedTransitions[$currentStage])) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid transition from $currentStage to $nextStage"]);
        exit;
    }

    $sql = "INSERT INTO application_history (app_id, stage) VALUES (:app_id, :stage)";
    $stmtHistory = $conn->prepare($sql);
    $stmtHistory->execute([':app_id' => $id, ':stage' => $nextStage]);

    // If moving to "decision" stage, prepare to send decision email
    if ($nextStage === "decision") {
        // Get candidate and job details for email
        $sqlCandidate = "SELECT c.email, c.firstname, c.lastname, j.title 
                         FROM application app
                         JOIN candidature c ON app.candidate_id = c.candidature_id
                         JOIN joboffer j ON app.joboffer_id = j.job_id
                         WHERE app.application_id = :id";
        $stmtCandidate = $conn->prepare($sqlCandidate);
        $stmtCandidate->execute([':id' => $id]);
        $candidateInfo = $stmtCandidate->fetch(PDO::FETCH_ASSOC);
        
        if ($candidateInfo) {
            // Note: Implement email sending here
            // sendDecisionEmail($candidateInfo['email'], $candidateInfo['firstname'], $candidateInfo['lastname'], $candidateInfo['title']);
            error_log("Ready to send decision email to: " . $candidateInfo['email']);
        }
    }

    echo json_encode(['success' => true, 'message' => "Stage updated to $nextStage"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
