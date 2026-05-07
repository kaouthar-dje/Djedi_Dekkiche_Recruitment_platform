<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? (int)$data['id'] : null;
$role = isset($data['user_role']) ? $data['user_role'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Candidate ID is required']);
    exit;
}

if ($role !== 'HR' && $role !== 'Recruiter') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Only HR/Recruiter can view candidate information']);
    exit;
}
$sql = "SELECT 
            c.*,
            app.application_id,
            app.score,
            app.status,
            j.title AS job_title,
            COALESCE(latest_stage.stage, 'screening') AS stage
        FROM candidature c
        LEFT JOIN application app ON app.candidate_id = c.candidature_id
        LEFT JOIN joboffer j ON app.joboffer_id = j.job_id
        LEFT JOIN (
            SELECT ah.app_id, ah.stage
            FROM application_history ah
            INNER JOIN (
                SELECT app_id, MAX(changed_at) AS max_changed_at
                FROM application_history
                GROUP BY app_id
            ) latest ON latest.app_id = ah.app_id AND latest.max_changed_at = ah.changed_at
        ) latest_stage ON latest_stage.app_id = app.application_id
        WHERE c.candidature_id = :id
        ORDER BY app.application_id DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($candidate);
?>
