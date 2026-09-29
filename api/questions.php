<?php
// api/questions.php
// Lesson Questions Fetch API (Language Independent)

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

// Handle POST submission or GET fetch
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST;
    }

    $userId = isset($input['user_id']) ? intval($input['user_id']) : (isset($input['userId']) ? intval($input['userId']) : 0);
    $questionId = isset($input['question_id']) ? trim($input['question_id']) : (isset($input['questionId']) ? trim($input['questionId']) : '');
    $userAnswer = isset($input['answer']) ? $input['answer'] : null;
    $interactionState = isset($input['interaction_state']) ? $input['interaction_state'] : (isset($input['interactionState']) ? $input['interactionState'] : null);

    if ($userId <= 0 || empty($questionId)) {
        sendResponse(400, ["status" => "error", "message" => "Valid user_id and question_id are required."]);
    }

    try {
        // Fetch Question Details
        $qStmt = $db->prepare("SELECT * FROM questions WHERE id = :id LIMIT 1");
        $qStmt->execute([':id' => $questionId]);
        $question = $qStmt->fetch();

        if (!$question) {
            sendResponse(404, ["status" => "error", "message" => "Question not found."]);
        }

        // Fetch User Details
        $uStmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $uStmt->execute([':id' => $userId]);
        $user = $uStmt->fetch();

        if (!$user) {
            sendResponse(404, ["status" => "error", "message" => "User not found."]);
        }

        // Validate Answer
        $isCorrect = false;
        $correctAnswerRaw = $question['correct_answer'];
        $decodedCorrect = json_decode($correctAnswerRaw, true);
        if ($decodedCorrect === null && json_last_error() !== JSON_ERROR_NONE) {
            $decodedCorrect = $correctAnswerRaw;
        }

        $type = $question['type'];

        if ($type === 'THEORY') {
            $isCorrect = true;
        } elseif ($type === 'MULTIPLE_CHOICE') {
            $expectedIndex = is_numeric($decodedCorrect) ? intval($decodedCorrect) : trim(strval($decodedCorrect));
            $providedIndex = is_numeric($userAnswer) ? intval($userAnswer) : trim(strval($userAnswer));
            $isCorrect = ($expectedIndex === $providedIndex);
        } elseif ($type === 'FILL_BLANK' || $type === 'CODE_COMPLETION') {
            $expected = strtolower(trim(strval(is_array($decodedCorrect) ? $decodedCorrect[0] : $decodedCorrect)));
            $provided = strtolower(trim(strval($userAnswer)));
            $isCorrect = ($expected === $provided);
        } elseif ($type === 'ARRAY_INTERACTION') {
            // Check interaction state array or userAnswer
            $submittedArray = null;
            if (is_array($interactionState) && isset($interactionState['array'])) {
                $submittedArray = $interactionState['array'];
            } elseif (is_array($userAnswer)) {
                $submittedArray = $userAnswer;
            } elseif (is_string($userAnswer)) {
                $decoded = json_decode($userAnswer, true);
                $submittedArray = is_array($decoded) ? $decoded : [$userAnswer];
            }

            if (is_array($decodedCorrect) && is_array($submittedArray)) {
                // Normalize string representations
                $normCorrect = array_values(array_map('strval', $decodedCorrect));
                $normSubmitted = array_values(array_map('strval', $submittedArray));
                $isCorrect = ($normCorrect === $normSubmitted);
            } elseif (is_string($decodedCorrect) && is_string($userAnswer)) {
                $isCorrect = (trim(strtolower($decodedCorrect)) === trim(strtolower($userAnswer)));
            }
        } else {
            // Fallback string / loose comparison
            $isCorrect = (strval($decodedCorrect) === strval($userAnswer));
        }

        // Ensure completion table exists before starting transaction
        $db->exec("CREATE TABLE IF NOT EXISTS `user_completed_questions` (
            `user_id` INT NOT NULL,
            `question_id` VARCHAR(50) NOT NULL,
            `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`, `question_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->beginTransaction();

        $xpAwarded = 0;
        $heartChange = 0;
        $currentHearts = intval($user['hearts']);
        $currentXp = intval($user['xp']);
        $currentLevel = intval($user['level']);
        $currentCrowns = intval($user['crowns']);

        $compCheck = $db->prepare("SELECT COUNT(*) FROM user_completed_questions WHERE user_id = :uid AND question_id = :qid");
        $compCheck->execute([':uid' => $userId, ':qid' => $questionId]);
        $alreadyCompleted = ($compCheck->fetchColumn() > 0);

        if ($isCorrect) {
            // Determine XP based on question type / challenge
            $isBoss = (strpos($questionId, 'boss') !== false || $questionId === 'a10');
            $baseXp = $isBoss ? 50 : 10;

            if (!$alreadyCompleted) {
                $xpAwarded = $baseXp;
                $currentXp += $xpAwarded;
                // Calculate level up if XP surpasses threshold (e.g., 200 XP per level)
                $currentLevel = max(1, intval(floor($currentXp / 200)) + 1);

                if ($isBoss) {
                    $currentCrowns += 1;
                }

                // Record completion
                $insComp = $db->prepare("INSERT IGNORE INTO user_completed_questions (user_id, question_id) VALUES (:uid, :qid)");
                $insComp->execute([':uid' => $userId, ':qid' => $questionId]);
            } else {
                // Modest repeat practice reward if desired (or 0)
                $xpAwarded = 0;
            }

            $updateUser = $db->prepare("UPDATE users SET xp = :xp, level = :level, crowns = :crowns WHERE id = :id");
            $updateUser->execute([
                ':xp' => $currentXp,
                ':level' => $currentLevel,
                ':crowns' => $currentCrowns,
                ':id' => $userId
            ]);

        } else {
            // Deduct 1 heart (min 0)
            if ($currentHearts > 0 && !$user['is_pro']) {
                $currentHearts = max(0, $currentHearts - 1);
                $heartChange = -1;
                $updateHeart = $db->prepare("UPDATE users SET hearts = :hearts WHERE id = :id");
                $updateHeart->execute([
                    ':hearts' => $currentHearts,
                    ':id' => $userId
                ]);
            }
        }

        $db->commit();

        sendResponse(200, [
            "success" => true,
            "correct" => $isCorrect,
            "xp_awarded" => $xpAwarded,
            "xpAwarded" => $xpAwarded,
            "hearts_remaining" => $currentHearts,
            "heartsRemaining" => $currentHearts,
            "current_xp" => $currentXp,
            "currentXp" => $currentXp,
            "level" => $currentLevel,
            "crowns" => $currentCrowns,
            "lesson_completed" => $isCorrect,
            "lessonCompleted" => $isCorrect,
            "next_lesson_unlocked" => $isCorrect,
            "nextLessonUnlocked" => $isCorrect,
            "explanation" => $question['explanation']
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendResponse(500, ["status" => "error", "message" => "Validation error: " . $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, ["status" => "error", "message" => "Method not allowed."]);
}

$categoryId = isset($_GET['category_id']) ? trim($_GET['category_id']) : (isset($_GET['categoryId']) ? trim($_GET['categoryId']) : '');

if (empty($categoryId)) {
    sendResponse(400, ["status" => "error", "message" => "category_id parameter is required."]);
}

try {
    $query = "SELECT * FROM questions WHERE category_id = :cat_id ORDER BY CAST(SUBSTRING(id, 2) AS UNSIGNED) ASC, id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':cat_id' => $categoryId
    ]);

    $questions = [];

    while ($row = $stmt->fetch()) {
        $options = $row['options'] ? json_decode($row['options'], true) : null;
        
        $correctAnswer = json_decode($row['correct_answer'], true);
        if ($correctAnswer === null && json_last_error() !== JSON_ERROR_NONE) {
            $correctAnswer = $row['correct_answer'];
            if (is_numeric($correctAnswer)) {
                $correctAnswer = intval($correctAnswer);
            }
        }

        $blanks = $row['blanks'] ? json_decode($row['blanks'], true) : null;
        $items = $row['items'] ? json_decode($row['items'], true) : null;
        $correctOrder = $row['correct_order'] ? json_decode($row['correct_order'], true) : null;
        $arrayData = $row['array_data'] ? json_decode($row['array_data'], true) : null;

        $question = [
            "id" => $row['id'],
            "type" => $row['type'],
            "question" => $row['question'],
            "correctAnswer" => $correctAnswer,
            "explanation" => $row['explanation']
        ];

        if ($options !== null) $question["options"] = $options;
        if ($row['code'] !== null) $question["code"] = $row['code'];
        if ($blanks !== null) $question["blanks"] = $blanks;
        if ($items !== null) $question["items"] = $items;
        if ($correctOrder !== null) $question["correctOrder"] = $correctOrder;
        if ($arrayData !== null) $question["arrayData"] = $arrayData;
        if ($row['image_url'] !== null) $question["imageUrl"] = $row['image_url'];

        $questions[] = $question;
    }

    echo json_encode($questions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    sendResponse(500, ["status" => "error", "message" => "Failed to fetch: " . $e->getMessage()]);
}
?>
