<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$plainPassword = $data['password'] ?? '';
$role = trim($data['role'] ?? '');
$allowedRoles = ['Candidate', 'HR', 'Recruiter'];

if ($username === '' || $email === '' || $plainPassword === '' || $role === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "All fields are required."]);
    exit;
}

if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid role. Allowed values are Candidate, HR, or Recruiter.",
        "received_role" => $role
    ]);
    exit;
}

$password = password_hash($plainPassword, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO `User` (username, email, password, role)
                            VALUES (:username, :email, :password, :role)");

    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role', $role);

    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
