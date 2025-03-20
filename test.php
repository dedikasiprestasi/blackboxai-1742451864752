<?php
require_once 'config.php';

// Validate access code
if (!isset($_GET['code'])) {
    header("Location: index.php");
    exit();
}

$access_code = $_GET['code'];

// Get exam details and validate access
$stmt = $pdo->prepare("
    SELECT e.*, al.id as access_link_id 
    FROM exams e 
    JOIN access_links al ON e.id = al.exam_id 
    WHERE al.access_code = ? AND al.is_active = 1
");
$stmt->execute([$access_code]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Invalid or expired access code.");
}

// Handle participant registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $stmt = $pdo->prepare("
        INSERT INTO participants (name, email, access_link_id, status)
        VALUES (?, ?, ?, 'not_started')
    ");
    $stmt->execute([$_POST['name'], $_POST['email'], $exam['access_link_id']]);
    $_SESSION['participant_id'] = $pdo->lastInsertId();
    $_SESSION['exam_start_time'] = date('Y-m-d H:i:s');
    
    // Update participant status
    $stmt = $pdo->prepare("
        UPDATE participants 
        SET status = 'in_progress', start_time = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['participant_id']]);
}

// Get questions if participant is registered
$questions = [];
if (isset($_SESSION['participant_id'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM questions 
        WHERE exam_id = ? 
        ORDER BY " . ($exam['is_random'] ? "RAND()" : "question_order")
    );
    $stmt->execute([$exam['id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> - CBT System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        :root {
            --bg-color: <?php echo $exam['background_color']; ?>;
            --text-color: <?php echo $exam['text_color']; ?>;
            --button-color: <?php echo $exam['button_color']; ?>;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .exam-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .exam-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .timer-container {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--button-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.2rem;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .question-container {
            margin-bottom: 30px;
        }

        .question-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .options-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .option-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .option-item:hover {
            border-color: var(--button-color);
            background-color: #f8f9fa;
        }

        .option-item.selected {
            border-color: var(--button-color);
            background-color: #e3f2fd;
        }

        .option-item input[type="radio"] {
            margin-right: 15px;
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: var(--button-color);
            color: white;
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .nav-btn:hover:not(:disabled) {
            opacity: 0.9;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--button-color);
            transition: width 0.3s ease;
        }

        .question-number {
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        /* Registration Form Styles */
        .registration-form {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--button-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['participant_id'])): ?>
        <!-- Registration Form -->
        <div class="registration-form">
            <h2>Enter Your Details</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" name="register" class="submit-btn">Start Exam</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Exam Interface -->
        <div class="timer-container" id="timer"></div>
        
        <div class="exam-container">
            <div class="exam-header">
                <h1><?php echo htmlspecialchars($exam['title']); ?></h1>
                <p><?php echo htmlspecialchars($exam['description']); ?></p>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" id="progressBar"></div>
            </div>

            <div id="questionContainer" class="question-container">
                <!-- Questions will be loaded here -->
            </div>

            <div class="navigation-buttons">
                <button id="prevBtn" class="nav-btn" onclick="previousQuestion()">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button id="nextBtn" class="nav-btn" onclick="nextQuestion()">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <script>
            // Questions data
            const questions = <?php echo json_encode($questions); ?>;
            let currentQuestionIndex = 0;
            const answers = new Array(questions.length).fill(null);
            
            // Timer setup
            const startTime = new Date('<?php echo $_SESSION['exam_start_time']; ?>').getTime();
            const duration = <?php echo $exam['duration']; ?> * 60 * 1000; // Convert minutes to milliseconds
            
            function updateTimer() {
                const now = new Date().getTime();
                const timeElapsed = now - startTime;
                const timeRemaining = duration - timeElapsed;
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    submitExam();
                    return;
                }
                
                const hours = Math.floor(timeRemaining / (1000 * 60 * 60));
                const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
                
                document.getElementById('timer').innerHTML = 
                    `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
            
            const timerInterval = setInterval(updateTimer, 1000);
            updateTimer();

            function displayQuestion(index) {
                const question = questions[index];
                const container = document.getElementById('questionContainer');
                
                container.innerHTML = `
                    <div class="question-number">Question ${index + 1} of ${questions.length}</div>
                    <div class="question-text">${question.question_text}</div>
                    <div class="options-container">
                        <label class="option-item ${answers[index] === 'A' ? 'selected' : ''}">
                            <input type="radio" name="q${index}" value="A" ${answers[index] === 'A' ? 'checked' : ''}>
                            ${question.option_a}
                        </label>
                        <label class="option-item ${answers[index] === 'B' ? 'selected' : ''}">
                            <input type="radio" name="q${index}" value="B" ${answers[index] === 'B' ? 'checked' : ''}>
                            ${question.option_b}
                        </label>
                        <label class="option-item ${answers[index] === 'C' ? 'selected' : ''}">
                            <input type="radio" name="q${index}" value="C" ${answers[index] === 'C' ? 'checked' : ''}>
                            ${question.option_c}
                        </label>
                        <label class="option-item ${answers[index] === 'D' ? 'selected' : ''}">
                            <input type="radio" name="q${index}" value="D" ${answers[index] === 'D' ? 'checked' : ''}>
                            ${question.option_d}
                        </label>
                    </div>
                `;

                // Update navigation buttons
                document.getElementById('prevBtn').disabled = index === 0;
                document.getElementById('nextBtn').innerHTML = 
                    index === questions.length - 1 ? 'Submit <i class="fas fa-check"></i>' : 'Next <i class="fas fa-arrow-right"></i>';

                // Update progress bar
                const progress = ((index + 1) / questions.length) * 100;
                document.getElementById('progressBar').style.width = `${progress}%`;

                // Add event listeners to options
                const options = container.querySelectorAll('input[type="radio"]');
                options.forEach(option => {
                    option.addEventListener('change', function() {
                        answers[index] = this.value;
                        updateOptionStyles();
                    });
                });
            }

            function updateOptionStyles() {
                const options = document.querySelectorAll('.option-item');
                options.forEach(option => {
                    const radio = option.querySelector('input[type="radio"]');
                    option.classList.toggle('selected', radio.checked);
                });
            }

            function previousQuestion() {
                if (currentQuestionIndex > 0) {
                    currentQuestionIndex--;
                    displayQuestion(currentQuestionIndex);
                }
            }

            function nextQuestion() {
                if (currentQuestionIndex < questions.length - 1) {
                    currentQuestionIndex++;
                    displayQuestion(currentQuestionIndex);
                } else {
                    submitExam();
                }
            }

            async function submitExam() {
                if (!confirm('Are you sure you want to submit the exam?')) {
                    return;
                }

                try {
                    const response = await fetch('submit_exam.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            answers: answers,
                            participant_id: <?php echo $_SESSION['participant_id']; ?>
                        })
                    });

                    if (response.ok) {
                        const result = await response.json();
                        window.location.href = `result.php?id=${result.result_id}`;
                    } else {
                        throw new Error('Failed to submit exam');
                    }
                } catch (error) {
                    alert('Error submitting exam. Please try again.');
                    console.error('Error:', error);
                }
            }

            // Initialize first question
            displayQuestion(currentQuestionIndex);

            // Handle keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft' && currentQuestionIndex > 0) {
                    previousQuestion();
                } else if (e.key === 'ArrowRight' && currentQuestionIndex < questions.length - 1) {
                    nextQuestion();
                } else if (e.key >= '1' && e.key <= '4') {
                    const optionMap = {'1': 'A', '2': 'B', '3': 'C', '4': 'D'};
                    answers[currentQuestionIndex] = optionMap[e.key];
                    displayQuestion(currentQuestionIndex);
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>