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
    // Extract form data from POST and FILES
    $lastname = $_POST['lastName'] ?? '';
    $firstname = $_POST['firstName'] ?? '';
    $telephone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $education = (int)($_POST['education'] ?? 0);
    $experience = (int)($_POST['experience'] ?? 0);
    $skills = (int)($_POST['skills'] ?? 0);
    $certifications = (int)($_POST['certifications'] ?? 0);
    $hadInternship = isset($_POST['internship']) && $_POST['internship'] === 'true' ? 1 : 0;
    $joboffer_id = (int)($_POST['jobOfferId'] ?? 0);
    $user_id = (int)($_POST['userId'] ?? 0);
    
    // Validate required fields
    if (!$lastname || !$firstname || !$email || !$telephone || $education < 0 || $experience < 0 || !$joboffer_id || !$user_id) {
        throw new Exception('Missing or invalid required fields');
    }
    
    // Handle file upload
    $cvpath = '';
    $cvfilename = '';
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        // Ensure uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $cvfilename = basename($_FILES['cv']['name']);
        $cvpath = $uploadDir . time() . '_' . $cvfilename;  // Add timestamp to avoid collisions
        if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cvpath)) {
            throw new Exception('Failed to upload CV file');
        }
    } else {
        throw new Exception('CV file upload failed or missing');
    }
    
    $score = ($experience * 4) + ($education * 3) + ($skills * 2) + $certifications + ($hadInternship ? 5 : 0);

    // Insert into candidature table
    $sql = "INSERT INTO candidature (lastname, firstname, cvpath, cvfilename, telephone, email, education, experience, skills, certifications, hadInternship) VALUES (:lastname, :firstname, :cvpath, :cvfilename, :telephone, :email, :education, :experience, :skills, :certifications, :hadInternship)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':lastname' => $lastname,
        ':firstname' => $firstname,
        ':cvpath' => $cvpath,
        ':cvfilename' => $cvfilename,
        ':telephone' => $telephone,
        ':email' => $email,
        ':education' => $education,
        ':experience' => $experience,
        ':skills' => $skills,
        ':certifications' => $certifications,
        ':hadInternship' => $hadInternship
    ]);

    // Get the newly inserted candidate ID
    $candidate_id = $conn->lastInsertId();
    
    // Insert into Application table 
    $sql2 = "INSERT INTO application (candidate_id, joboffer_id, user_id, score, status) VALUES (:candidate_id, :joboffer_id, :user_id, :score, :status)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([
        ':candidate_id' => $candidate_id,
        ':joboffer_id' => $joboffer_id,
        ':user_id' => $user_id,
        ':score' => $score,
        ':status' => 'candidating'
    ]);

    $application_id = $conn->lastInsertId();
    $historyStmt = $conn->prepare("INSERT INTO application_history (app_id, stage) VALUES (:application_id, :stage)");
    $historyStmt->execute([
        ':application_id' => $application_id,
        ':stage' => 'screening'
    ]);
    
    echo json_encode(['message' => 'Application submitted successfully', 'candidate_id' => $candidate_id]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
