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
    $user_id = $data['user_id'] ?? null;
    $user_role = $data['user_role'] ?? null;
    
    if (!$user_id || $user_role !== 'Candidate') {
        http_response_code(400);
        echo json_encode(['error' => 'A candidate user_id is required']);
        exit;
    }
    
    // Get user profile information
    $sqlUser = "SELECT user_id AS id, username, email, role FROM `User` WHERE user_id = :id";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->execute([':id' => $user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Get all candidatures for this user
    $sqlCandidatures = "SELECT 
                            app.application_id AS id,
                            app.status,
                            app.score,
                            c.firstname,
                            c.lastname,
                            c.email,
                            c.experience,
                            j.title as job_title,
                            j.department,
                            COALESCE(latest_stage.stage, 'screening') AS stage
                        FROM application app
                        JOIN candidature c ON app.candidate_id = c.candidature_id
                        JOIN joboffer j ON app.joboffer_id = j.job_id
                        LEFT JOIN (
                            SELECT ah.app_id, ah.stage
                            FROM application_history ah
                            INNER JOIN (
                                SELECT app_id, MAX(changed_at) AS max_changed_at
                                FROM application_history
                                GROUP BY app_id
                            ) latest ON latest.app_id = ah.app_id AND latest.max_changed_at = ah.changed_at
                        ) latest_stage ON latest_stage.app_id = app.application_id
                        WHERE app.user_id = :user_id
                        ORDER BY app.application_id DESC";
    $stmtCandidatures = $conn->prepare($sqlCandidatures);
    $stmtCandidatures->execute([':user_id' => $user_id]);
    $candidatures = $stmtCandidatures->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'user' => $user,
        'candidatures' => $candidatures
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
