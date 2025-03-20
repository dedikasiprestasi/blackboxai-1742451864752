<?php
require_once 'config.php';

// Ensure request is POST and contains JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['participant_id']) || !isset($data['answers'])) {
    http_response_code(400);
    die('Invalid data');
}

try {
    $pdo->beginTransaction();

    // Get participant info
    $stmt = $pdo->prepare("
        SELECT p.*, al.exam_id 
        FROM participants p 
        JOIN access_links al ON p.access_link_id = al.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$data['participant_id']]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participant) {
        throw new Exception('Participant not found');
    }

    // Get questions and calculate score
    $stmt = $pdo->prepare("SELECT id, correct_option, points FROM questions WHERE exam_id = ?");
    $stmt->execute([$participant['exam_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPoints = 0;
    $earnedPoints = 0;
    $correctAnswers = 0;

    // Insert answers and calculate score
    $insertStmt = $pdo->prepare("
        INSERT INTO answers (participant_id, question_id, selected_option, is_correct) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($questions as $index => $question) {
        $selectedOption = $data['answers'][$index];
        $isCorrect = $selectedOption === $question['correct_option'];
        
        $insertStmt->execute([
            $data['participant_id'],
            $question['id'],
            $selectedOption,
            $isCorrect ? 1 : 0
        ]);

        $totalPoints += $question['points'];
        if ($isCorrect) {
            $earnedPoints += $question['points'];
            $correctAnswers++;
        }
    }

    // Calculate percentage score
    $score = ($earnedPoints / $totalPoints) * 100;

    // Update participant record
    $stmt = $pdo->prepare("
        UPDATE participants 
        SET status = 'completed',
            end_time = NOW(),
            score = ?
        WHERE id = ?
    ");
    $stmt->execute([$score, $data['participant_id']]);

    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'result_id' => $data['participant_id'],
        'score' => $score,
        'correct_answers' => $correctAnswers,
        'total_questions' => count($questions)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>