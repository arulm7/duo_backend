<?php
// api/user.php
// User Stats, Profile, Achievements & Progression API (Language Independent)

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

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

function checkAndUnlockAchievements($db, $userId) {
    $userQuery = "SELECT xp, streak FROM users WHERE id = :id";
    $stmt = $db->prepare($userQuery);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) return [];

    $unlocked = [];
    $existingQuery = "SELECT achievement_id FROM user_achievements WHERE user_id = :user_id";
    $stmt = $db->prepare($existingQuery);
    $stmt->execute([':user_id' => $userId]);
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('first_step', $existing) && $user['xp'] >= 50) {
        $unlocked[] = 'first_step';
    }

    if (!in_array('streak_3', $existing) && $user['streak'] >= 3) {
        $unlocked[] = 'streak_3';
    }

    if (!in_array('array_master', $existing)) {
        $checkArr = "SELECT COUNT(*) as completed FROM user_completed_questions uc 
                     JOIN questions q ON uc.question_id = q.id 
                     WHERE uc.user_id = :user_id AND q.category_id = 'array'";
        $stmt = $db->prepare($checkArr);
        $stmt->execute([':user_id' => $userId]);
        $arrResult = $stmt->fetch();
        if (intval($arrResult['completed']) >= 14) {
            $unlocked[] = 'array_master';
        }
    }

    if (!empty($unlocked)) {
        $insert = "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (:user_id, :ach_id)";
        $stmt = $db->prepare($insert);
        foreach ($unlocked as $achId) {
            $stmt->execute([':user_id' => $userId, ':ach_id' => $achId]);
            
            $xpQuery = "SELECT xp_reward FROM achievements WHERE id = :id";
            $xpStmt = $db->prepare($xpQuery);
            $xpStmt->execute([':id' => $achId]);
            $reward = $xpStmt->fetchColumn();
            
            if ($reward) {
                $updateXp = "UPDATE users SET xp = xp + :reward WHERE id = :user_id";
                $updateStmt = $db->prepare($updateXp);
                $updateStmt->execute([':reward' => $reward, ':user_id' => $userId]);
            }
        }
    }

    return $unlocked;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'profile') {
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        if ($userId <= 0) {
            sendResponse(400, ["status" => "error", "message" => "Valid user_id parameter is required."]);
        }

        $profile = fetchUserProfile($db, $userId);
        if (!$profile) {
            sendResponse(404, ["status" => "error", "message" => "User not found."]);
        }

        sendResponse(200, [
            "status" => "success",
            "user" => $profile
        ]);
    } else {
        sendResponse(400, ["status" => "error", "message" => "Invalid action."]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST;
    }

    $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
    if ($userId <= 0) {
        sendResponse(400, ["status" => "error", "message" => "Valid user_id is required."]);
    }

    if ($action === 'update_stats') {
        $xpGain = isset($input['xp_gain']) ? intval($input['xp_gain']) : 0;
        $hearts = isset($input['hearts']) ? intval($input['hearts']) : null;
        $streak = isset($input['streak']) ? intval($input['streak']) : null;
        $level = isset($input['level']) ? intval($input['level']) : null;
        $crowns = isset($input['crowns']) ? intval($input['crowns']) : null;

        try {
            $db->beginTransaction();

            $fields = ["xp = xp + :xp_gain"];
            $params = [':xp_gain' => $xpGain, ':user_id' => $userId];

            if ($hearts !== null) {
                $fields[] = "hearts = :hearts";
                $params[':hearts'] = $hearts;
            }
            if ($streak !== null) {
                $fields[] = "streak = :streak";
                $params[':streak'] = $streak;
            }
            if ($level !== null) {
                $fields[] = "level = :level";
                $params[':level'] = $level;
            }
            if ($crowns !== null) {
                $fields[] = "crowns = :crowns";
                $params[':crowns'] = $crowns;
            }

            $updateQuery = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :user_id";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute($params);

            $db->commit();

            $newUnlocked = checkAndUnlockAchievements($db, $userId);

            $profile = fetchUserProfile($db, $userId);
            sendResponse(200, [
                "status" => "success",
                "message" => "Stats updated successfully.",
                "unlockedAchievements" => $newUnlocked,
                "user" => $profile
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            sendResponse(500, ["status" => "error", "message" => "Failed to update stats: " . $e->getMessage()]);
        }

    } elseif ($action === 'complete_question') {
        $questionId = isset($input['question_id']) ? trim($input['question_id']) : '';

        if (empty($questionId)) {
            sendResponse(400, ["status" => "error", "message" => "question_id is required."]);
        }

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `user_completed_questions` (
                `user_id` INT NOT NULL,
                `question_id` VARCHAR(50) NOT NULL,
                `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`user_id`, `question_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $insert = "INSERT IGNORE INTO user_completed_questions (user_id, question_id) VALUES (:user_id, :question_id)";
            $stmt = $db->prepare($insert);
            $stmt->execute([
                ':user_id' => $userId,
                ':question_id' => $questionId
            ]);

            checkAndUnlockAchievements($db, $userId);

            $profile = fetchUserProfile($db, $userId);
            sendResponse(200, [
                "status" => "success",
                "message" => "Question completion recorded.",
                "user" => $profile
            ]);

        } catch (Exception $e) {
            sendResponse(500, ["status" => "error", "message" => "Failed to record: " . $e->getMessage()]);
        }
    } else {
        sendResponse(400, ["status" => "error", "message" => "Invalid action."]);
    }
}
?>
