<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: documents.php");
    exit;
}

$document_id = $_GET['id'];

// Get document details
$stmt = $pdo->prepare("
    SELECT 
        d.*, 
        u.name as uploader_name, 
        p.name as project_name, 
        t.title as task_title 
    FROM documents d 
    LEFT JOIN users u ON d.uploaded_by_id = u.id 
    LEFT JOIN projects p ON d.project_id = p.id 
    LEFT JOIN tasks t ON d.task_id = t.id 
    WHERE d.id = ?
");
$stmt->execute([$document_id]);
$document = $stmt->fetch();

if (!$document) {
    header("Location: documents.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Details - WeProject</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-kanban fs-4 me-2"></i>
                        <h5 class="mb-0">WeProject</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house-door me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">
                                <i class="bi bi-folder me-2"></i> Projects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tasks.php">
                                <i class="bi bi-check2-square me-2"></i> Tasks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="documents.php">
                                <i class="bi bi-file-earmark me-2"></i> Documents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="teams.php">
                                <i class="bi bi-people me-2"></i> Teams
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] == 'ADMIN'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-person-gear me-2"></i> Users
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2"></i>
                            <strong><?php echo $_SESSION['user_name']; ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Document Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="documents.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $document['uploaded_by_id'] == $_SESSION['user_id']): ?>
                            <a href="form_document.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($document['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <p><strong>Description:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($document['description'] ?? 'No description')); ?></p>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">File Information</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2"><strong>Category:</strong> <?php echo htmlspecialchars($document['category'] ?? '-'); ?></li>
                                            <li class="mb-2"><strong>Version:</strong> <?php echo htmlspecialchars($document['version'] ?? '1.0'); ?></li>
                                            <li class="mb-2"><strong>Uploaded By:</strong> <?php echo htmlspecialchars($document['uploader_name']); ?></li>
                                            <li class="mb-2"><strong>Date:</strong> <?php echo date('d M Y H:i', strtotime($document['uploaded_at'])); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Relations</h6>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Project
                                        <?php if ($document['project_name']): ?>
                                            <a href="project_detail.php?id=<?php echo $document['project_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($document['project_name']); ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Task
                                        <?php if ($document['task_title']): ?>
                                            <a href="task_detail.php?id=<?php echo $document['task_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($document['task_title']); ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6 text-end align-self-end">
                                <a href="uploads/<?php echo $document['file_path']; ?>" class="btn btn-primary" target="_blank">
                                    <i class="bi bi-download me-2"></i> Download / View File
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
