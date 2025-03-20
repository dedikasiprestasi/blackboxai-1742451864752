<?php
require_once '../config.php';
requireLogin();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert exam details
        $stmt = $pdo->prepare("
            INSERT INTO exams (
                title, description, duration, passing_score, 
                is_random, background_color, text_color, button_color
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['duration'],
            $_POST['passing_score'],
            isset($_POST['is_random']) ? 1 : 0,
            $_POST['background_color'],
            $_POST['text_color'],
            $_POST['button_color']
        ]);

        $exam_id = $pdo->lastInsertId();

        // Insert questions
        $question_count = count($_POST['questions']);
        $stmt = $pdo->prepare("
            INSERT INTO questions (
                exam_id, question_text, option_a, option_b, 
                option_c, option_d, correct_option, explanation, 
                points, question_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        for ($i = 0; $i < $question_count; $i++) {
            $stmt->execute([
                $exam_id,
                $_POST['questions'][$i],
                $_POST['options_a'][$i],
                $_POST['options_b'][$i],
                $_POST['options_c'][$i],
                $_POST['options_d'][$i],
                $_POST['correct_options'][$i],
                $_POST['explanations'][$i],
                $_POST['points'][$i],
                $i + 1
            ]);
        }

        $pdo->commit();
        $success_message = "Exam created successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error creating exam: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Exam - CBT System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>CBT Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="exams.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Manage Exams</span>
                        </a>
                    </li>
                    <li>
                        <a href="questions.php">
                            <i class="fas fa-question-circle"></i>
                            <span>Questions Bank</span>
                        </a>
                    </li>
                    <li>
                        <a href="participants.php">
                            <i class="fas fa-users"></i>
                            <span>Participants</span>
                        </a>
                    </li>
                    <li>
                        <a href="results.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Results</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                </div>
            </div>

            <div class="dashboard-content">
                <h2>Create New Exam</h2>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="admin-form">
                    <form method="POST" action="" id="examForm">
                        <!-- Exam Details Section -->
                        <div class="form-section">
                            <h3>Exam Details</h3>
                            <div class="form-row">
                                <label for="title">Exam Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            <div class="form-row">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" required></textarea>
                            </div>
                            <div class="form-row">
                                <label for="duration">Duration (minutes)</label>
                                <input type="number" id="duration" name="duration" min="1" required>
                            </div>
                            <div class="form-row">
                                <label for="passing_score">Passing Score (%)</label>
                                <input type="number" id="passing_score" name="passing_score" min="0" max="100" required>
                            </div>
                            <div class="form-row">
                                <label class="custom-control">
                                    <input type="checkbox" name="is_random">
                                    <span class="checkmark"></span>
                                    Randomize Questions
                                </label>
                            </div>
                        </div>

                        <!-- Design Customization -->
                        <div class="form-section">
                            <h3>Design Customization</h3>
                            <div class="form-row">
                                <label>Background Color</label>
                                <div class="color-picker">
                                    <input type="color" name="background_color" value="#ffffff">
                                </div>
                            </div>
                            <div class="form-row">
                                <label>Text Color</label>
                                <div class="color-picker">
                                    <input type="color" name="text_color" value="#000000">
                                </div>
                            </div>
                            <div class="form-row">
                                <label>Button Color</label>
                                <div class="color-picker">
                                    <input type="color" name="button_color" value="#4CAF50">
                                </div>
                            </div>
                        </div>

                        <!-- Questions Section -->
                        <div class="form-section">
                            <h3>Questions</h3>
                            <div id="questions-container">
                                <!-- Questions will be added here dynamically -->
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="addQuestion()">
                                <i class="fas fa-plus"></i> Add Question
                            </button>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Exam</button>
                            <a href="exams.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Template (Hidden) -->
    <template id="question-template">
        <div class="question-box">
            <div class="question-header">
                <h4>Question #<span class="question-number"></span></h4>
                <button type="button" class="btn-remove" onclick="removeQuestion(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="form-row">
                <label>Question Text</label>
                <textarea name="questions[]" required></textarea>
            </div>
            <div class="form-row">
                <label>Option A</label>
                <input type="text" name="options_a[]" required>
            </div>
            <div class="form-row">
                <label>Option B</label>
                <input type="text" name="options_b[]" required>
            </div>
            <div class="form-row">
                <label>Option C</label>
                <input type="text" name="options_c[]" required>
            </div>
            <div class="form-row">
                <label>Option D</label>
                <input type="text" name="options_d[]" required>
            </div>
            <div class="form-row">
                <label>Correct Option</label>
                <select name="correct_options[]" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="form-row">
                <label>Explanation</label>
                <textarea name="explanations[]" required></textarea>
            </div>
            <div class="form-row">
                <label>Points</label>
                <input type="number" name="points[]" value="1" min="1" required>
            </div>
        </div>
    </template>

    <script>
        // Toggle sidebar
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });

        // Question management
        let questionCount = 0;

        function addQuestion() {
            questionCount++;
            const template = document.getElementById('question-template');
            const container = document.getElementById('questions-container');
            const clone = template.content.cloneNode(true);
            
            // Update question number
            clone.querySelector('.question-number').textContent = questionCount;
            
            container.appendChild(clone);
        }

        function removeQuestion(button) {
            const questionBox = button.closest('.question-box');
            questionBox.remove();
            
            // Update question numbers
            const questions = document.querySelectorAll('.question-box');
            questions.forEach((q, index) => {
                q.querySelector('.question-number').textContent = index + 1;
            });
            questionCount = questions.length;
        }

        // Add first question by default
        addQuestion();

        // Form validation
        document.getElementById('examForm').addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('.question-box');
            if (questions.length === 0) {
                e.preventDefault();
                alert('Please add at least one question to the exam.');
            }
        });
    </script>
</body>
</html>