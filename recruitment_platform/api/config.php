<?php
$host = 'localhost';
$dbname = 'recruitment_platform';
$username = 'root';
$password = '';

try {
    $conn = new PDO ("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Authorization function to check user role
function checkAuthorization($requiredRole = null) {
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = $data['user_id'] ?? null;
    $userRole = $data['user_role'] ?? null;
    
    if (!$userId || !$userRole) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: Missing authentication']);
        exit;
    }
    
    if ($requiredRole && $userRole !== $requiredRole && !in_array($userRole, (array)$requiredRole)) {
        http_response_code(403);
        echo json_encode(['error' => "Forbidden access: only {$requiredRole} can perform this action."]);
        exit;
    }
    
    return ['user_id' => $userId, 'user_role' => $userRole];
}

// Email validation function
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Only allow Gmail and Outlook .com domains
    $domain = substr(strrchr($email, "@"), 1);
    $allowedDomains = ['gmail.com', 'outlook.com'];
    return in_array($domain, $allowedDomains);
}