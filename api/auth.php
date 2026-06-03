<?php
// api/auth.php
// User Registration & Login endpoint (Language Independent)

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, ["status" => "error", "message" => "Method not allowed. Use POST."]);
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    $input = $_POST;
}

function fetchUserProfile($db, $userId) {
    $userQuery = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($userQuery);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return null;
    }

    $achievQuery = "SELECT a.id, a.title, a.description, a.icon, ua.unlocked_at, a.xp_reward 
                    FROM user_achievements ua
                    JOIN achievements a ON ua.achievement_id = a.id
                    WHERE ua.user_id = :user_id";
    $stmt = $db->prepare($achievQuery);
    $stmt->execute([':user_id' => $userId]);
    $achievements = [];
    while ($row = $stmt->fetch()) {
        $achievements[] = [
            "id" => $row['id'],
            "title" => $row['title'],
            "description" => $row['description'],
            "icon" => $row['icon'],
            "unlockedAt" => $row['unlocked_at'],
            "xpReward" => intval($row['xp_reward'])
        ];
    }

    return [
        "id" => strval($user['id']),
        "username" => $user['username'],
        "email" => $user['email'],
        "level" => intval($user['level']),
        "xp" => intval($user['xp']),
        "streak" => intval($user['streak']),
        "hearts" => intval($user['hearts']),
        "crowns" => intval($user['crowns']),
        "isPro" => $user['is_pro'] ? true : false,
        "achievements" => $achievements,
        "dailyGoal" => intval($user['daily_goal']),
        "joinDate" => $user['join_date']
    ];
}

if ($action === 'register') {
    $username = isset($input['username']) ? trim($input['username']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';

    if (empty($username) || empty($email) || empty($password)) {
        sendResponse(400, ["status" => "error", "message" => "Please complete all fields."]);
    }

    $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute([':username' => $username, ':email' => $email]);
    if ($stmt->fetch()) {
        sendResponse(409, ["status" => "error", "message" => "Username or email is already registered."]);
    }

    try {
        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        $insertQuery = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        
        $stmt = $db->prepare($insertQuery);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPass
        ]);

        $newUserId = $db->lastInsertId();

        $profile = fetchUserProfile($db, $newUserId);
        sendResponse(201, [
            "status" => "success",
            "message" => "User registered successfully.",
            "user" => $profile
        ]);

    } catch (Exception $e) {
        sendResponse(500, ["status" => "error", "message" => "Failed to register: " . $e->getMessage()]);
    }

} elseif ($action === 'login') {
    $usernameOrEmail = isset($input['username']) ? trim($input['username']) : (isset($input['email']) ? trim($input['email']) : '');
    $password = isset($input['password']) ? trim($input['password']) : '';

    if (empty($usernameOrEmail) || empty($password)) {
        sendResponse(400, ["status" => "error", "message" => "Username/Email and Password are required."]);
    }

    $loginQuery = "SELECT id, password FROM users WHERE username = :val OR email = :val LIMIT 1";
    $stmt = $db->prepare($loginQuery);
    $stmt->execute([':val' => $usernameOrEmail]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        sendResponse(401, ["status" => "error", "message" => "Invalid credentials."]);
    }

    $profile = fetchUserProfile($db, $user['id']);
    sendResponse(200, [
        "status" => "success",
        "message" => "Logged in successfully.",
        "user" => $profile
    ]);
} else {
    sendResponse(400, ["status" => "error", "message" => "Invalid action."]);
}
?>
