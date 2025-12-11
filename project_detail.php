<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

 $project_id = $_GET['id'];

 $stmt = $pdo->prepare("SELECT p.*, u.name as manager_name FROM projects p LEFT JOIN users u ON p.manager_id = u.id WHERE p.id = ?");
 $stmt->execute([$project_id]);
 $project = $stmt->fetch();

if (!$project) {
    header("Location: projects.php");
    exit;
}

 $stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name
    FROM users u
    JOIN team_members tm ON u.id = tm.user_id
    JOIN project_team pt ON tm.team_id = pt.team_id
    WHERE pt.project_id = ?
    ORDER BY u.name
");
 $stmt->execute([$project_id]);
 $project_members = $stmt->fetchAll();

 $stmt = $pdo->prepare("
    SELECT 
        t.*, 
        p.name as project_name, 
        u.name as assignee_name  -- Ambil nama user langsung
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee = u.id
    WHERE t.project_id = ?
    ORDER BY t.status, t.due_date
");
 $stmt->execute([$project_id]);
 $tasks = $stmt->fetchAll();

 $task_counts = [
    'TO_DO' => 0,
    'IN_PROGRESS' => 0,
    'REVIEW' => 0,
    'DONE' => 0
];

foreach ($tasks as $task) {
    $task_counts[$task['status']]++;
}

 $total_tasks = count($tasks);
 $completed_tasks = $task_counts['DONE'];
 $progress = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

 $stmt = $pdo->prepare("SELECT d.*, u.name as uploader_name FROM documents d LEFT JOIN users u ON d.uploaded_by_id = u.id WHERE d.project_id = ? ORDER BY d.uploaded_at DESC");
 $stmt->execute([$project_id]);
 $documents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project['name']; ?> - Sistem Manajemen Proyek</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .task-column {
            min-height: 400px;
        }
        .task-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .task-card:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="pt-3">
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

            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $project['name']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="projects.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $project['manager_id'] == $_SESSION['user_id']): ?>
                            <a href="form_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Project Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <strong>Status:</strong>
                                        <span class="badge bg-<?php 
                                            echo match($project['status']) {
                                                'PLANNING' => 'info',
                                                'ACTIVE' => 'success',
                                                'ON_HOLD' => 'warning',
                                                'COMPLETED' => 'primary',
                                                'CANCELLED' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>"><?php echo $project['status']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Priority:</strong>
                                        <span class="badge bg-<?php 
                                            echo match($project['priority']) {
                                                'LOW' => 'success',
                                                'MEDIUM' => 'info',
                                                'HIGH' => 'warning',
                                                'CRITICAL' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>"><?php echo $project['priority']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Progress:</strong> <?php echo $progress; ?>%
                                    </div>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $progress; ?>%</div>
                                </div>
                                <p><strong>Description:</strong> <?php echo $project['description'] ?? 'No description'; ?></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Project Manager:</strong> <?php echo $project['manager_name'] ?? 'None'; ?></p>
                                        <p><strong>Start Date:</strong> <?php echo date('d M Y', strtotime($project['start_date'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Created At:</strong> <?php echo date('d M Y', strtotime($project['created_at'])); ?></p>
                                        <p><strong>End Date:</strong> <?php echo date('d M Y', strtotime($project['end_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Team Members</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($project_members)): ?>
                                    <p>No team members yet.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($project_members as $member): ?>
                                        <li class="list-group-item">
                                            <?php echo htmlspecialchars($member['name']); ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Tasks</h5>
                                <a href="form_task.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> New Task
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-4">
                                        <div class="card bg-light task-column">
                                            <div class="card-header">
                                                <h6 class="mb-0">To Do (<?php echo $task_counts['TO_DO']; ?>)</h6>
                                            </div>
                                            <div class="card-body p-2">
                                                <a href="form_task.php?project_id=<?php echo $project_id; ?>&status=TO_DO" class="btn btn-sm btn-outline-primary mb-2">
                                                    <i class="bi bi-plus-lg me-2"></i> Create
                                                </a>
                                                <?php foreach ($tasks as $task): ?>
                                                    <?php if ($task['status'] == 'TO_DO'): ?>
                                                    <div class="card mb-2 task-card" onclick="window.location='task_detail.php?id=<?php echo $task['id']; ?>'">
                                                        <div class="card-body p-2">
                                                            <h6 class="card-title mb-1"><?php echo $task['title']; ?></h6>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted"><?php echo $task['assignee_name'] ?? 'None'; ?></small>
                                                                <span class="badge bg-<?php
                                                                    echo match($task['priority']) {
                                                                        'LOW' => 'success',
                                                                        'MEDIUM' => 'info',
                                                                        'HIGH' => 'warning',
                                                                        'CRITICAL' => 'danger',
                                                                        default => 'secondary'
                                                                    };
                                                                ?> rounded-pill"><?php echo $task['priority']; ?></span>
                                                            </div>
                                                            <?php if ($task['due_date']): ?>
                                                            <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('d M', strtotime($task['due_date'])); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4">
                                        <div class="card bg-light task-column">
                                            <div class="card-header">
                                                <h6 class="mb-0">In Progress (<?php echo $task_counts['IN_PROGRESS']; ?>)</h6>
                                            </div>
                                            <div class="card-body p-2">
                                                <a href="form_task.php?project_id=<?php echo $project_id; ?>&status=IN_PROGRESS" class="btn btn-sm btn-outline-primary mb-2">
                                                    <i class="bi bi-plus-lg me-2"></i> Create
                                                </a>
                                                <?php foreach ($tasks as $task): ?>
                                                    <?php if ($task['status'] == 'IN_PROGRESS'): ?>
                                                    <div class="card mb-2 task-card" onclick="window.location='task_detail.php?id=<?php echo $task['id']; ?>'">
                                                        <div class="card-body p-2">
                                                            <h6 class="card-title mb-1"><?php echo $task['title']; ?></h6>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted"><?php echo $task['assignee_name'] ?? 'None'; ?></small>
                                                                <span class="badge bg-<?php
                                                                    echo match($task['priority']) {
                                                                        'LOW' => 'success',
                                                                        'MEDIUM' => 'info',
                                                                        'HIGH' => 'warning',
                                                                        'CRITICAL' => 'danger',
                                                                        default => 'secondary'
                                                                    };
                                                                ?> rounded-pill"><?php echo $task['priority']; ?></span>
                                                            </div>
                                                            <?php if ($task['due_date']): ?>
                                                            <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('d M', strtotime($task['due_date'])); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4">
                                        <div class="card bg-light task-column">
                                            <div class="card-header">
                                                <h6 class="mb-0">Review (<?php echo $task_counts['REVIEW']; ?>)</h6>
                                            </div>
                                            <div class="card-body p-2">
                                                <a href="form_task.php?project_id=<?php echo $project_id; ?>&status=REVIEW" class="btn btn-sm btn-outline-primary mb-2">
                                                    <i class="bi bi-plus-lg me-2"></i> Create
                                                </a>
                                                <?php foreach ($tasks as $task): ?>
                                                    <?php if ($task['status'] == 'REVIEW'): ?>
                                                    <div class="card mb-2 task-card" onclick="window.location='task_detail.php?id=<?php echo $task['id']; ?>'">
                                                        <div class="card-body p-2">
                                                            <h6 class="card-title mb-1"><?php echo $task['title']; ?></h6>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted"><?php echo $task['assignee_name'] ?? 'None'; ?></small>
                                                                <span class="badge bg-<?php
                                                                    echo match($task['priority']) {
                                                                        'LOW' => 'success',
                                                                        'MEDIUM' => 'info',
                                                                        'HIGH' => 'warning',
                                                                        'CRITICAL' => 'danger',
                                                                        default => 'secondary'
                                                                    };
                                                                ?> rounded-pill"><?php echo $task['priority']; ?></span>
                                                            </div>
                                                            <?php if ($task['due_date']): ?>
                                                            <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('d M', strtotime($task['due_date'])); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4">
                                        <div class="card bg-light task-column">
                                            <div class="card-header">
                                                <h6 class="mb-0">Done (<?php echo $task_counts['DONE']; ?>)</h6>
                                            </div>
                                            <div class="card-body p-2">
                                                <a href="form_task.php?project_id=<?php echo $project_id; ?>&status=DONE" class="btn btn-sm btn-outline-primary mb-2">
                                                    <i class="bi bi-plus-lg me-2"></i> Create
                                                </a>
                                                <?php foreach ($tasks as $task): ?>
                                                    <?php if ($task['status'] == 'DONE'): ?>
                                                    <div class="card mb-2 task-card" onclick="window.location='task_detail.php?id=<?php echo $task['id']; ?>'">
                                                        <div class="card-body p-2">
                                                            <h6 class="card-title mb-1"><?php echo $task['title']; ?></h6>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted"><?php echo $task['assignee_name'] ?? 'None'; ?></small>
                                                                <span class="badge bg-<?php
                                                                    echo match($task['priority']) {
                                                                        'LOW' => 'success',
                                                                        'MEDIUM' => 'info',
                                                                        'HIGH' => 'warning',
                                                                        'CRITICAL' => 'danger',
                                                                        default => 'secondary'
                                                                    };
                                                                ?> rounded-pill"><?php echo $task['priority']; ?></span>
                                                            </div>
                                                            <?php if ($task['due_date']): ?>
                                                            <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('d M', strtotime($task['due_date'])); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Documents</h5>
                                <a href="form_document.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-upload me-1"></i> Upload Document
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($documents)): ?>
                                    <p>No documents for this project yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Document Name</th>
                                                    <th>Category</th>

                                                    <th>Uploaded By</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($documents as $document): ?>
                                                <tr>
                                                    <td>
                                                        <a href="uploads/<?php echo $document['file_path']; ?>" target="_blank">
                                                            <?php echo $document['title']; ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo $document['category'] ?? 'None'; ?></td>

                                                    <td><?php echo $document['uploader_name']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($document['uploaded_at'])); ?></td>
                                                    <td>
                                                        <a href="document_detail.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="uploads/<?php echo $document['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $document['uploaded_by_id'] == $_SESSION['user_id']): ?>
                                                        <a href="form_document.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger delete-document" data-id="<?php echo $document['id']; ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>


    
    <div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDocumentModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this document? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteDocumentBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteDocumentModal = new bootstrap.Modal(document.getElementById('deleteDocumentModal'));
            const confirmDeleteDocumentBtn = document.getElementById('confirmDeleteDocumentBtn');
            
            document.querySelectorAll('.delete-document').forEach(button => {
                button.addEventListener('click', function() {
                    const documentId = this.getAttribute('data-id');
                    confirmDeleteDocumentBtn.href = `handle_delete_document.php?id=${documentId}`;
                    deleteDocumentModal.show();
                });
            });
        });
    </script>
</body>
</html>