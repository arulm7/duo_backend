<?php
// api/leaderboard.php
// Global Users Leaderboard API

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, ["status" => "error", "message" => "Method not allowed."]);
}

try {
    $query = "SELECT id, username, xp, streak FROM users ORDER BY xp DESC";
    $stmt = $db->query($query);
    
    $leaderboard = [];
    $rank = 1;

    $avatars = ['🧙‍♂️', '🥷', '👾', '🚀', '🐱', '🐼', '🦊', '🦁'];

    while ($row = $stmt->fetch()) {
        $avatarIndex = intval($row['id']) % count($avatars);
        $avatar = $avatars[$avatarIndex];

        $leaderboard[] = [
            "id" => strval($row['id']),
            "username" => $row['username'],
            "xp" => intval($row['xp']),
            "streak" => intval($row['streak']),
            "rank" => $rank,
            "avatar" => $avatar
        ];
        $rank++;
    }

    sendResponse(200, [
        "status" => "success",
        "leaderboard" => $leaderboard
    ]);

} catch (Exception $e) {
    sendResponse(500, ["status" => "error", "message" => "Failed to fetch leaderboard: " . $e->getMessage()]);
}
?>
