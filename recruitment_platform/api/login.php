<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (($email === '' && $username === '') || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your username or email, and your password.'
    ]);
    exit;
}

$params = [];
if ($email !== '' && $username !== '') {
    $sql = "SELECT user_id, email, username, password, role FROM `User` WHERE email = ? AND username = ?";
    $params = [$email, $username];
} elseif ($email !== '') {
    $sql = "SELECT user_id, email, username, password, role FROM `User` WHERE email = ?";
    $params = [$email];
} else {
    $sql = "SELECT user_id, email, username, password, role FROM `User` WHERE username = ?";
    $params = [$username];
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$authenticatedUser = null;

foreach ($users as $candidate) {
    $storedPassword = $candidate['password'];
    $isHashedMatch = password_verify($password, $storedPassword);
    $isLegacyPlaintextMatch = $password === $storedPassword;

    if ($isHashedMatch || $isLegacyPlaintextMatch) {
        if ($isLegacyPlaintextMatch) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE `User` SET password = ? WHERE user_id = ?");
            $updateStmt->execute([$newHash, $candidate['user_id']]);
            $candidate['password'] = $newHash;
        }

        $authenticatedUser = $candidate;
        break;
    }
}

if ($authenticatedUser) {
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'id' => $authenticatedUser['user_id'],
        'email' => $authenticatedUser['email'],
        'username' => $authenticatedUser['username'],
        'role' => $authenticatedUser['role']
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username/email or password'
    ]);
}
?>
