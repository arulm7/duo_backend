<?php
// api/admin.php
// Dedicated DSALINGO Mobile Admin CRUD API Controller

require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Fetch Admin dashboard overview stats
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'stats') {
    try {
        // Count users (excluding admin user if needed, but let's count all)
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        // Count questions
        $questionCount = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();

        // Count challenges
        $challengeCount = $db->query("SELECT COUNT(*) FROM challenges")->fetchColumn();

        sendResponse(200, [
            "status" => "success",
            "totalUsers" => intval($userCount),
            "totalQuestions" => intval($questionCount),
            "totalChallenges" => intval($challengeCount)
        ]);
    } catch (Exception $e) {
        sendResponse(500, ["status" => "error", "message" => "Failed to load admin stats: " . $e->getMessage()]);
    }
}

// All modifying actions require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(405, ["status" => "error", "message" => "Method not allowed."]);
    }
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    $input = $_POST;
}

if ($action === 'add_question') {
    $id = isset($input['id']) ? trim($input['id']) : '';
    $categoryId = isset($input['category_id']) ? trim($input['category_id']) : '';
    $type = isset($input['type']) ? trim($input['type']) : 'MULTIPLE_CHOICE';
    $questionText = isset($input['question']) ? trim($input['question']) : '';
    $options = isset($input['options']) ? $input['options'] : null;
    $correctAnswer = isset($input['correct_answer']) ? $input['correct_answer'] : '';
    $explanation = isset($input['explanation']) ? trim($input['explanation']) : '';
    $code = isset($input['code']) ? trim($input['code']) : null;
    $blanks = isset($input['blanks']) ? $input['blanks'] : null;
    $items = isset($input['items']) ? $input['items'] : null;
    $correctOrder = isset($input['correct_order']) ? $input['correct_order'] : null;
    $arrayData = isset($input['array_data']) ? $input['array_data'] : null;

    if (empty($id) || empty($categoryId) || empty($questionText) || empty($explanation)) {
        sendResponse(400, ["status" => "error", "message" => "Required fields missing (id, category_id, question, explanation)."]);
    }

    try {
        // Check if ID is unique
        $check = $db->prepare("SELECT id FROM questions WHERE id = :id LIMIT 1");
        $check->execute([':id' => $id]);
        if ($check->fetch()) {
            sendResponse(409, ["status" => "error", "message" => "A question with ID '$id' already exists."]);
        }

        $query = "INSERT INTO questions (id, category_id, type, question, options, correct_answer, explanation, code, blanks, items, correct_order, array_data) 
                  VALUES (:id, :category_id, :type, :question, :options, :correct_answer, :explanation, :code, :blanks, :items, :correct_order, :array_data)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id' => $id,
            ':category_id' => $categoryId,
            ':type' => $type,
            ':question' => $questionText,
            ':options' => $options ? json_encode($options) : null,
            ':correct_answer' => is_array($correctAnswer) ? json_encode($correctAnswer) : $correctAnswer,
            ':explanation' => $explanation,
            ':code' => $code ? $code : null,
            ':blanks' => $blanks ? json_encode($blanks) : null,
            ':items' => $items ? json_encode($items) : null,
            ':correct_order' => $correctOrder ? json_encode($correctOrder) : null,
            ':array_data' => $arrayData ? json_encode($arrayData) : null
        ]);

        sendResponse(201, ["status" => "success", "message" => "Question added successfully!"]);
    } catch (Exception $e) {
        sendResponse(500, ["status" => "error", "message" => "Failed to add question: " . $e->getMessage()]);
    }
}

elseif ($action === 'edit_question') {
    $id = isset($input['id']) ? trim($input['id']) : '';
    $categoryId = isset($input['category_id']) ? trim($input['category_id']) : '';
    $type = isset($input['type']) ? trim($input['type']) : 'MULTIPLE_CHOICE';
    $questionText = isset($input['question']) ? trim($input['question']) : '';
    $options = isset($input['options']) ? $input['options'] : null;
    $correctAnswer = isset($input['correct_answer']) ? $input['correct_answer'] : '';
    $explanation = isset($input['explanation']) ? trim($input['explanation']) : '';
    $code = isset($input['code']) ? trim($input['code']) : null;
    $blanks = isset($input['blanks']) ? $input['blanks'] : null;
    $items = isset($input['items']) ? $input['items'] : null;
    $correctOrder = isset($input['correct_order']) ? $input['correct_order'] : null;
    $arrayData = isset($input['array_data']) ? $input['array_data'] : null;

    if (empty($id) || empty($categoryId) || empty($questionText) || empty($explanation)) {
        sendResponse(400, ["status" => "error", "message" => "Required fields missing (id, category_id, question, explanation)."]);
    }

    try {
        $query = "UPDATE questions SET 
                    category_id = :category_id, 
                    type = :type, 
                    question = :question, 
                    options = :options, 
                    correct_answer = :correct_answer, 
                    explanation = :explanation, 
                    code = :code, 
                    blanks = :blanks, 
                    items = :items, 
                    correct_order = :correct_order, 
                    array_data = :array_data
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id' => $id,
            ':category_id' => $categoryId,
            ':type' => $type,
            ':question' => $questionText,
            ':options' => $options ? json_encode($options) : null,
            ':correct_answer' => is_array($correctAnswer) ? json_encode($correctAnswer) : $correctAnswer,
            ':explanation' => $explanation,
            ':code' => $code ? $code : null,
            ':blanks' => $blanks ? json_encode($blanks) : null,
            ':items' => $items ? json_encode($items) : null,
            ':correct_order' => $correctOrder ? json_encode($correctOrder) : null,
            ':array_data' => $arrayData ? json_encode($arrayData) : null
        ]);

        sendResponse(200, ["status" => "success", "message" => "Question updated successfully!"]);
    } catch (Exception $e) {
        sendResponse(500, ["status" => "error", "message" => "Failed to update question: " . $e->getMessage()]);
    }
}

elseif ($action === 'delete_question') {
    $id = isset($input['id']) ? trim($input['id']) : '';

    if (empty($id)) {
        sendResponse(400, ["status" => "error", "message" => "question id is required."]);
    }

    try {
        $stmt = $db->prepare("DELETE FROM questions WHERE id = :id");
        $stmt->execute([':id' => $id]);

        sendResponse(200, ["status" => "success", "message" => "Question deleted successfully!"]);
    } catch (Exception $e) {
        sendResponse(500, ["status" => "error", "message" => "Failed to delete question: " . $e->getMessage()]);
    }
}

else {
    sendResponse(400, ["status" => "error", "message" => "Invalid admin action requested."]);
}
?>
