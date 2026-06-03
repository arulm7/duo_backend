<?php
// api/challenges.php
// Striver Curated Code Challenges API

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    try {
        $query = "SELECT 
                    c.id, 
                    c.title, 
                    c.description, 
                    c.difficulty, 
                    c.category, 
                    c.xp_reward, 
                    c.time_limit, 
                    c.link,
                    IF(uc.is_completed IS NOT NULL AND uc.is_completed = 1, 1, 0) as is_completed
                  FROM challenges c
                  LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.user_id = :user_id
                  ORDER BY c.difficulty ASC, c.id ASC";
                  
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $challenges = $stmt->fetchAll();

        $result = [];
        foreach ($challenges as $c) {
            $result[] = [
                "id" => $c['id'],
                "title" => $c['title'],
                "description" => $c['description'],
                "difficulty" => $c['difficulty'],
                "category" => $c['category'],
                "xpReward" => intval($c['xp_reward']),
                "timeLimit" => $c['time_limit'] !== null ? intval($c['time_limit']) : null,
                "isCompleted" => intval($c['is_completed']) === 1,
                "link" => $c['link']
            ];
        }

        sendResponse(200, [
            "status" => "success",
            "challenges" => $result
        ]);

    } catch (Exception $e) {
        sendResponse(500, ["status" => "error", "message" => "Failed to fetch challenges: " . $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'complete') {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) {
            $input = $_POST;
        }

        $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
        $challengeId = isset($input['challenge_id']) ? trim($input['challenge_id']) : '';

        if ($userId <= 0 || empty($challengeId)) {
            sendResponse(400, ["status" => "error", "message" => "Valid user_id and challenge_id are required."]);
        }

        try {
            $db->beginTransaction();

            $chalQuery = "SELECT xp_reward FROM challenges WHERE id = :id";
            $stmt = $db->prepare($chalQuery);
            $stmt->execute([':id' => $challengeId]);
            $challenge = $stmt->fetch();

            if (!$challenge) {
                $db->rollBack();
                sendResponse(404, ["status" => "error", "message" => "Challenge not found."]);
            }

            $xpReward = intval($challenge['xp_reward']);

            $insertQuery = "INSERT INTO user_challenges (user_id, challenge_id, is_completed) 
                            VALUES (:user_id, :challenge_id, 1) 
                            ON DUPLICATE KEY UPDATE is_completed = 1";
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([
                ':user_id' => $userId,
                ':challenge_id' => $challengeId
            ]);

            $updateUser = "UPDATE users SET xp = xp + :xp_reward WHERE id = :user_id";
            $stmt = $db->prepare($updateUser);
            $stmt->execute([
                ':xp_reward' => $xpReward,
                ':user_id' => $userId
            ]);



            $checkAchQuery = "SELECT COUNT(*) FROM user_achievements WHERE user_id = :user_id AND achievement_id = 'leetcode_slayer'";
            $stmt = $db->prepare($checkAchQuery);
            $stmt->execute([':user_id' => $userId]);
            $hasAch = $stmt->fetchColumn();

            $unlockedAchievements = [];
            if (!$hasAch) {
                $insAch = "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (:user_id, 'leetcode_slayer')";
                $stmt = $db->prepare($insAch);
                $stmt->execute([':user_id' => $userId]);

                $achXpQuery = "SELECT xp_reward FROM achievements WHERE id = 'leetcode_slayer'";
                $achXp = $db->query($achXpQuery)->fetchColumn();
                if ($achXp) {
                    $updateUserXp = "UPDATE users SET xp = xp + :ach_xp WHERE id = :user_id";
                    $stmt = $db->prepare($updateUserXp);
                    $stmt->execute([':ach_xp' => $achXp, ':user_id' => $userId]);
                }
                $unlockedAchievements[] = 'leetcode_slayer';
            }

            $db->commit();

            sendResponse(200, [
                "status" => "success",
                "message" => "Challenge completed!",
                "xpEarned" => $xpReward,
                "unlockedAchievements" => $unlockedAchievements
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            sendResponse(500, ["status" => "error", "message" => "Failed to complete challenge: " . $e->getMessage()]);
        }
    } else {
        sendResponse(400, ["status" => "error", "message" => "Invalid action."]);
    }
}
?>
