<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Get participant results
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        e.title as exam_title,
        e.passing_score,
        e.background_color,
        e.text_color,
        e.button_color
    FROM participants p
    JOIN access_links al ON p.access_link_id = al.id
    JOIN exams e ON al.exam_id = e.id
    WHERE p.id = ?
");
$stmt->execute([$_GET['id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("Result not found.");
}

// Get detailed answers
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        q.question_text,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_option,
        q.explanation
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    WHERE a.participant_id = ?
    ORDER BY q.question_order
");
$stmt->execute([$_GET['id']]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isPassed = $result['score'] >= $result['passing_score'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - CBT System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        :root {
            --bg-color: <?php echo $result['background_color']; ?>;
            --text-color: <?php echo $result['text_color']; ?>;
            --button-color: <?php echo $result['button_color']; ?>;
            --success-color: #4CAF50;
            --error-color: #f44336;
            --warning-color: #ff9800;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .result-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .result-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .result-header h1 {
            color: var(--button-color);
            margin-bottom: 10px;
            font-size: 2em;
        }

        .score-section {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 10px solid;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .score-circle::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid #eee;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .passed .score-circle {
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .failed .score-circle {
            border-color: var(--error-color);
            color: var(--error-color);
        }

        .result-status {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 10px;
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .passed .result-status {
            color: var(--success-color);
        }

        .failed .result-status {
            color: var(--error-color);
        }

        .summary-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .answers-section {
            margin-top: 40px;
        }

        .answer-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .answer-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .answer-item.correct {
            border-left: 4px solid var(--success-color);
        }

        .answer-item.incorrect {
            border-left: 4px solid var(--error-color);
        }

        .question-text {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .options-list {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
        }

        .option-item {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            background: #fff;
            border: 1px solid #eee;
            transition: all 0.2s ease;
        }

        .option-item.selected {
            background: #e3f2fd;
            border-color: #2196F3;
        }

        .option-item.correct {
            background: #e8f5e9;
            border-color: var(--success-color);
        }

        .option-item.incorrect {
            background: #ffebee;
            border-color: var(--error-color);
        }

        .explanation {
            background: #fff3e0;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            border-left: 4px solid var(--warning-color);
        }

        .explanation-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--warning-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .explanation-title i {
            font-size: 1.2em;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--button-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 8px;
        }

        .actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .result-container {
                box-shadow: none;
            }

            .btn {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .result-container {
                padding: 15px;
            }

            .score-circle {
                width: 120px;
                height: 120px;
                font-size: 2rem;
            }

            .summary-item {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-header">
            <h1><?php echo htmlspecialchars($result['exam_title']); ?></h1>
            <p>Exam Results for <?php echo htmlspecialchars($result['name']); ?></p>
        </div>

        <div class="score-section <?php echo $isPassed ? 'passed' : 'failed'; ?>">
            <div class="score-circle">
                <?php echo round($result['score']); ?>%
            </div>
            <div class="result-status">
                <?php echo $isPassed ? 'PASSED' : 'FAILED'; ?>
            </div>
            <p>Passing Score: <?php echo $result['passing_score']; ?>%</p>
        </div>

        <div class="summary-section">
            <div class="summary-item">
                <span>Start Time:</span>
                <span><?php echo date('Y-m-d H:i:s', strtotime($result['start_time'])); ?></span>
            </div>
            <div class="summary-item">
                <span>End Time:</span>
                <span><?php echo date('Y-m-d H:i:s', strtotime($result['end_time'])); ?></span>
            </div>
            <div class="summary-item">
                <span>Duration:</span>
                <span>
                    <?php 
                    $duration = strtotime($result['end_time']) - strtotime($result['start_time']);
                    echo sprintf('%02d:%02d:%02d', 
                        ($duration/3600), 
                        ($duration/60%60), 
                        $duration%60);
                    ?>
                </span>
            </div>
            <div class="summary-item">
                <span>Total Questions:</span>
                <span><?php echo count($answers); ?></span>
            </div>
            <div class="summary-item">
                <span>Correct Answers:</span>
                <span><?php echo array_sum(array_column($answers, 'is_correct')); ?></span>
            </div>
        </div>

        <div class="answers-section">
            <h2>Detailed Results</h2>
            <?php foreach ($answers as $index => $answer): ?>
                <div class="answer-item <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                    <div class="question-text">
                        <strong>Question <?php echo $index + 1; ?>:</strong>
                        <?php echo htmlspecialchars($answer['question_text']); ?>
                    </div>
                    <ul class="options-list">
                        <li class="option-item <?php 
                            echo $answer['selected_option'] === 'A' ? 'selected' : '';
                            echo $answer['correct_option'] === 'A' ? ' correct' : '';
                            echo $answer['selected_option'] === 'A' && $answer['selected_option'] !== $answer['correct_option'] ? ' incorrect' : '';
                        ?>">
                            A. <?php echo htmlspecialchars($answer['option_a']); ?>
                            <?php if ($answer['correct_option'] === 'A'): ?>
                                <i class="fas fa-check"></i>
                            <?php endif; ?>
                        </li>
                        <li class="option-item <?php 
                            echo $answer['selected_option'] === 'B' ? 'selected' : '';
                            echo $answer['correct_option'] === 'B' ? ' correct' : '';
                            echo $answer['selected_option'] === 'B' && $answer['selected_option'] !== $answer['correct_option'] ? ' incorrect' : '';
                        ?>">
                            B. <?php echo htmlspecialchars($answer['option_b']); ?>
                            <?php if ($answer['correct_option'] === 'B'): ?>
                                <i class="fas fa-check"></i>
                            <?php endif; ?>
                        </li>
                        <li class="option-item <?php 
                            echo $answer['selected_option'] === 'C' ? 'selected' : '';
                            echo $answer['correct_option'] === 'C' ? ' correct' : '';
                            echo $answer['selected_option'] === 'C' && $answer['selected_option'] !== $answer['correct_option'] ? ' incorrect' : '';
                        ?>">
                            C. <?php echo htmlspecialchars($answer['option_c']); ?>
                            <?php if ($answer['correct_option'] === 'C'): ?>
                                <i class="fas fa-check"></i>
                            <?php endif; ?>
                        </li>
                        <li class="option-item <?php 
                            echo $answer['selected_option'] === 'D' ? 'selected' : '';
                            echo $answer['correct_option'] === 'D' ? ' correct' : '';
                            echo $answer['selected_option'] === 'D' && $answer['selected_option'] !== $answer['correct_option'] ? ' incorrect' : '';
                        ?>">
                            D. <?php echo htmlspecialchars($answer['option_d']); ?>
                            <?php if ($answer['correct_option'] === 'D'): ?>
                                <i class="fas fa-check"></i>
                            <?php endif; ?>
                        </li>
                    </ul>
                    <?php if (!$answer['is_correct'] || true): ?>
                        <div class="explanation">
                            <div class="explanation-title">
                                <i class="fas fa-info-circle"></i> Explanation
                            </div>
                            <?php echo htmlspecialchars($answer['explanation']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn">
                <i class="fas fa-print"></i> Print Results
            </button>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        // Add smooth scrolling to all answer items
        document.querySelectorAll('.answer-item').forEach(item => {
            item.addEventListener('click', () => {
                item.style.transform = 'scale(1.01)';
                setTimeout(() => {
                    item.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html>