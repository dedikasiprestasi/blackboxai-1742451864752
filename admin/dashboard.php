<?php
require_once '../config.php';
requireLogin();

// Get total exams count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM exams");
$totalExams = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total active participants
$stmt = $pdo->query("SELECT COUNT(*) as total FROM participants WHERE status = 'in_progress'");
$activeParticipants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent exams
$stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC LIMIT 5");
$recentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CBT System</title>
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
                    <li class="active">
                        <a href="dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
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
                <h2>Dashboard Overview</h2>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Exams</h3>
                            <p><?php echo $totalExams; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Active Participants</h3>
                            <p><?php echo $activeParticipants; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Exams -->
                <div class="recent-section">
                    <h3>Recent Exams</h3>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentExams as $exam): ?>
                                <tr>
                                    <td><?php echo sanitize($exam['title']); ?></td>
                                    <td><?php echo $exam['duration']; ?> minutes</td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($exam['created_at'])); ?></td>
                                    <td>
                                        <a href="view_exam.php?id=<?php echo $exam['id']; ?>" class="btn-small">View</a>
                                        <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" class="btn-small">Edit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="create_exam.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Exam
                        </a>
                        <a href="generate_link.php" class="btn btn-secondary">
                            <i class="fas fa-link"></i> Generate Access Link
                        </a>
                        <a href="view_results.php" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>