<?php
// api/questions.php
// Lesson Questions Fetch API (Language Independent)

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$categoryId = isset($_GET['category_id']) ? trim($_GET['category_id']) : (isset($_GET['categoryId']) ? trim($_GET['categoryId']) : '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, ["status" => "error", "message" => "Method not allowed."]);
}

if (empty($categoryId)) {
    sendResponse(400, ["status" => "error", "message" => "category_id parameter is required."]);
}

try {
    $query = "SELECT * FROM questions WHERE category_id = :cat_id ORDER BY id ASC";
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

        $questions[] = $question;
    }

    echo json_encode($questions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    sendResponse(500, ["status" => "error", "message" => "Failed to fetch: " . $e->getMessage()]);
}
?>
