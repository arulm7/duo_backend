<?php
// api/learn.php
// Categories & User Progress API (Language Independent)

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, ["status" => "error", "message" => "Method not allowed."]);
}

$db->exec("CREATE TABLE IF NOT EXISTS `user_completed_questions` (
    `user_id` INT NOT NULL,
    `question_id` VARCHAR(50) NOT NULL,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

try {
    $query = "SELECT 
                c.id, 
                c.title, 
                c.icon, 
                c.color, 
                c.is_coming_soon,
                COUNT(DISTINCT q.id) as total_questions,
                COUNT(DISTINCT uc.question_id) as completed_questions
              FROM categories c
              LEFT JOIN questions q ON c.id = q.category_id
              LEFT JOIN user_completed_questions uc ON q.id = uc.question_id AND uc.user_id = :user_id
              GROUP BY c.id";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $categories = $stmt->fetchAll();

    $result = [];

    foreach ($categories as $cat) {
        $catId = $cat['id'];
        $totalQuestions = intval($cat['total_questions']);
        $completedQuestions = intval($cat['completed_questions']);

        if ($totalQuestions === 0) {
            $totalQuestions = ($catId === 'array') ? 14 : (($catId === 'basics') ? 5 : 10);
        }

        $colorHex = $cat['color'];
        $colorVal = hexdec(str_replace('0x', '', $colorHex));
        if (strpos($colorHex, '0xFF') === 0) {
            $colorVal = floatval($colorVal);
        }

        $result[] = [
            "id" => $catId,
            "title" => $cat['title'],
            "icon" => $cat['icon'],
            "color" => $colorVal,
            "totalQuestions" => $totalQuestions,
            "completedQuestions" => $completedQuestions,
            "isComingSoon" => $cat['is_coming_soon'] ? true : false
        ];
    }

    sendResponse(200, [
        "status" => "success",
        "categories" => $result
    ]);

} catch (Exception $e) {
    sendResponse(500, ["status" => "error", "message" => "Failed to fetch: " . $e->getMessage()]);
}
?>
