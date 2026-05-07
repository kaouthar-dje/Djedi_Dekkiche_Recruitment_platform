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

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_role = $data['user_role'] ?? null;
    $user_id = $data['user_id'] ?? null;
    
    // Authorization: Only HR/Recruiter can fetch all candidatures
    if (!$user_role || ($user_role !== 'Recruiter' && $user_role !== 'HR')) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized Action: Only HR/Recruiter can view all candidatures']);
        exit;
    }

    $sql = "SELECT 
                app.application_id AS application_id,
                c.candidature_id AS candidate_id,
                c.firstname,
                c.lastname,
                c.email,
                c.telephone,
                c.education,
                c.experience,
                c.skills,
                c.certifications,
                c.cvpath,
                c.cvfilename,
                c.hadInternship,
                app.score,
                app.status,
                j.job_id AS joboffer_id,
                j.title AS job_title,
                j.department,
                COALESCE(latest_stage.stage, 'screening') AS stage
            FROM application app
            JOIN candidature c ON app.candidate_id = c.candidature_id
            JOIN joboffer j ON app.joboffer_id = j.job_id
            LEFT JOIN (
                SELECT ah.app_id, ah.stage
                FROM application_history ah
                INNER JOIN (
                    SELECT app_id, MAX(application_history_id) AS latest_history_id
                    FROM application_history
                    GROUP BY app_id
                ) latest ON latest.latest_history_id = ah.application_history_id
            ) latest_stage ON latest_stage.app_id = app.application_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $candidate = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($candidate);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
