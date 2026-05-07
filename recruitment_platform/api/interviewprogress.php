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

checkAuthorization(['HR', 'Recruiter']);

// Get all interviews with application and candidate info
$sql = "SELECT 
    i.interview_id,
    i.appli_id,
    i.interviewer_id,
    i.interview_date,
    i.feedback,
    i.rating,
    a.application_id as app_id,
    a.score,
    a.status,
    c.candidature_id as candidate_id,
    c.firstname,
    c.lastname,
    c.email,
    j.job_id as job_id,
    j.title as job_title,
    u.username as interviewer_name,
    COALESCE(latest_stage.stage, 'screening') AS stage
FROM interview i
JOIN application a ON i.appli_id = a.application_id
JOIN candidature c ON a.candidate_id = c.candidature_id
JOIN joboffer j ON a.joboffer_id = j.job_id
JOIN `User` u ON i.interviewer_id = u.user_id
LEFT JOIN (
    SELECT ah.app_id, ah.stage
    FROM application_history ah
    INNER JOIN (
        SELECT app_id, MAX(application_history_id) AS latest_history_id
        FROM application_history
        GROUP BY app_id
    ) latest ON latest.latest_history_id = ah.application_history_id
) latest_stage ON latest_stage.app_id = a.application_id
WHERE latest_stage.stage = 'interviews'
ORDER BY i.interview_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format response
$result = [];
foreach ($interviews as $interview) {
    $result[] = [
        'interview_id' => $interview['interview_id'],
        'application_id' => $interview['appli_id'],
        'candidate' => [
            'id' => $interview['candidate_id'],
            'firstname' => $interview['firstname'],
            'lastname' => $interview['lastname'],
            'email' => $interview['email']
        ],
        'job' => [
            'id' => $interview['job_id'],
            'title' => $interview['job_title']
        ],
        'application' => [
            'score' => $interview['score'],
            'stage' => $interview['stage'],
            'status' => $interview['status']
        ],
        'interview_date' => $interview['interview_date'],
        'interviewer_name' => $interview['interviewer_name'],
        'feedback' => $interview['feedback'],
        'rating' => $interview['rating'],
        'has_feedback' => !is_null($interview['feedback'])
    ];
}

echo json_encode(['interviews' => $result]);
?>
