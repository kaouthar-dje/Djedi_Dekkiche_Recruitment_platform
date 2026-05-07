<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get stage-based counts
    $sql = "SELECT latest.stage, COUNT(*) as count
            FROM application_history latest
            INNER JOIN (
                SELECT app_id, MAX(application_history_id) AS latest_history_id
                FROM application_history
                GROUP BY app_id
            ) grouped ON grouped.latest_history_id = latest.application_history_id
            GROUP BY latest.stage";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stageCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total applications
    $sqlTotal = "SELECT COUNT(*) as total FROM application";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->execute();
    $totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    // Get total job offers
    $sqlJobOffers = "SELECT COUNT(*) as total FROM joboffer";
    $stmtJobOffers = $conn->prepare($sqlJobOffers);
    $stmtJobOffers->execute();
    $jobOffersResult = $stmtJobOffers->fetch(PDO::FETCH_ASSOC);
    
    // Get interviews count
    $sqlInterviews = "SELECT COUNT(*) as total FROM interview";
    $stmtInterviews = $conn->prepare($sqlInterviews);
    $stmtInterviews->execute();
    $interviewsResult = $stmtInterviews->fetch(PDO::FETCH_ASSOC);
    // Active vs eliminated applications
    $sqlStatus = "SELECT 
                    SUM(CASE WHEN status = 'candidating' THEN 1 ELSE 0 END) AS active_applications,
                    SUM(CASE WHEN status = 'eliminated' THEN 1 ELSE 0 END) AS eliminated_applications
                  FROM application";
    $stmtStatus = $conn->prepare($sqlStatus);
    $stmtStatus->execute();
    $statusResult = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    // Average score
    $sqlAverageScore = "SELECT ROUND(AVG(score), 1) AS avg_score FROM application";
    $stmtAverageScore = $conn->prepare($sqlAverageScore);
    $stmtAverageScore->execute();
    $averageScoreResult = $stmtAverageScore->fetch(PDO::FETCH_ASSOC);

    // Top departments by applications
    $sqlTopDepartments = "SELECT 
                            j.department,
                            COUNT(*) AS application_count
                          FROM application a
                          JOIN joboffer j ON a.joboffer_id = j.job_id
                          GROUP BY j.department
                          ORDER BY application_count DESC, j.department ASC
                          LIMIT 5";
    $stmtTopDepartments = $conn->prepare($sqlTopDepartments);
    $stmtTopDepartments->execute();
    $topDepartments = $stmtTopDepartments->fetchAll(PDO::FETCH_ASSOC);

    // Top job offers by applications
    $sqlTopOffers = "SELECT 
                        j.title,
                        j.department,
                        COUNT(*) AS application_count
                     FROM application a
                     JOIN joboffer j ON a.joboffer_id = j.job_id
                     GROUP BY j.job_id, j.title, j.department
                     ORDER BY application_count DESC, j.title ASC
                     LIMIT 5";
    $stmtTopOffers = $conn->prepare($sqlTopOffers);
    $stmtTopOffers->execute();
    $topOffers = $stmtTopOffers->fetchAll(PDO::FETCH_ASSOC);

    // Closing soon offers
    $sqlClosingSoon = "SELECT COUNT(*) AS total
                       FROM joboffer
                       WHERE enddate BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 14 DAY)";
    $stmtClosingSoon = $conn->prepare($sqlClosingSoon);
    $stmtClosingSoon->execute();
    $closingSoonResult = $stmtClosingSoon->fetch(PDO::FETCH_ASSOC);

    $totalApplications = (int)($totalResult['total'] ?? 0);
    $stageBreakdownWithPercentages = array_map(function ($stage) use ($totalApplications) {
        $count = (int)$stage['count'];
        $stage['count'] = $count;
        $stage['percentage'] = $totalApplications > 0 ? round(($count / $totalApplications) * 100, 1) : 0;
        return $stage;
    }, $stageCounts);
    
    $stats = [
        'stage_breakdown' => $stageBreakdownWithPercentages,
        'total_applications' => $totalApplications,
        'total_job_offers' => (int)($jobOffersResult['total'] ?? 0),
        'total_interviews_scheduled' => (int)($interviewsResult['total'] ?? 0),
        'active_applications' => (int)($statusResult['active_applications'] ?? 0),
        'eliminated_applications' => (int)($statusResult['eliminated_applications'] ?? 0),
        'average_score' => $averageScoreResult['avg_score'] !== null ? (float)$averageScoreResult['avg_score'] : 0,
        'offers_closing_soon' => (int)($closingSoonResult['total'] ?? 0),
        'top_departments' => $topDepartments,
        'top_offers' => $topOffers
    ];
    
    echo json_encode($stats);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
