<?php
require_once 'auth_check.php';
require_once 'db_connect.php';


$stmt = $pdo->query("
    SELECT 
        t.*, 
        p.name as project_name, 
        u.id as assignee_id,
        u.name as assignee_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee = u.id
    ORDER BY t.due_date ASC, t.id
");

$tasks = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $taskId = $row['id'];

    if (!isset($tasks[$taskId])) {
        $tasks[$taskId] = [
            'id'            => $row['id'],
            'title'         => $row['title'],
            'project_id'    => $row['project_id'],
            'project_name'  => $row['project_name'],
            'priority'      => $row['priority'],
            'status'        => $row['status'],
            'due_date'      => $row['due_date'],
            'created_by_id' => $row['created_by_id'], 
            'assignees'     => [] 
        ];
    }


    if ($row['assignee_id']) {
        $tasks[$taskId]['assignees'][] = [
            'id'   => $row['assignee_id'],
            'name' => $row['assignee_name']
        ];
    }
}

// get projects for filter dropdown
$stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
$projects = $stmt->fetchAll();

// get users for assignee filter dropdown
$stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - WeProject</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .task-card {
            transition: transform 0.3s;
        }
        .task-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
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
                            <a class="nav-link active" href="tasks.php">
                                <i class="bi bi-check2-square me-2"></i> Tasks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="documents.php">
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tasks</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_task.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> New Task
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <select class="form-select" id="projectFilter">
                            <option value="">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo $project['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="assigneeFilter">
                            <option value="">All Assignees</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="TO_DO">To Do</option>
                            <option value="IN_PROGRESS">In Progress</option>
                            <option value="REVIEW">Review</option>
                            <option value="DONE">Done</option>
                        </select>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="alert alert-info">No tasks yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Task Title</th>
                                            <th>Project</th>
                                            <th>Assignee</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasksTableBody">
                                        <?php foreach ($tasks as $task): 
                                            
                                            $idsArray = array_column($task['assignees'], 'id');
                                            $idsString = implode(',', $idsArray); 

                                            $namesArray = array_column($task['assignees'], 'name');
                                            $namesString = implode(', ', $namesArray);
                                        ?>
                                        <tr 
                                            data-project-id="<?php echo $task['project_id']; ?>" 
                                            data-assignee-ids="<?php echo htmlspecialchars($idsString); ?>" 
                                            data-status="<?php echo $task['status']; ?>">
                                            
                                            <td><a href="task_detail.php?id=<?php echo $task['id']; ?>"><?php echo htmlspecialchars($task['title']); ?></a></td>
                                            <td><a href="project_detail.php?id=<?php echo $task['project_id']; ?>"><?php echo htmlspecialchars($task['project_name']); ?></a></td>
                                            
                                            <td><?php echo $namesString ?: 'None'; ?></td>
                                            
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($task['priority']) {
                                                        'LOW' => 'success',
                                                        'MEDIUM' => 'info',
                                                        'HIGH' => 'warning',
                                                        'CRITICAL' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $task['priority']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($task['status']) {
                                                        'TO_DO' => 'secondary',
                                                        'IN_PROGRESS' => 'primary',
                                                        'REVIEW' => 'warning',
                                                        'DONE' => 'success',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $task['status']; ?></span>
                                            </td>
                                            <td><?php echo $task['due_date'] ? date('d M Y', strtotime($task['due_date'])) : '-'; ?></td>
                                            <td>
                                                <a href="task_detail.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $task['created_by_id'] == $_SESSION['user_id']): ?>
                                                <a href="form_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger delete-task" data-id="<?php echo $task['id']; ?>">
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
            </main>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this task? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            // Delete task functionality
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            document.querySelectorAll('.delete-task').forEach(button => {
                button.addEventListener('click', function() {
                    const taskId = this.getAttribute('data-id');
                    confirmDeleteBtn.href = `handle_delete_task.php?id=${taskId}`;
                    deleteModal.show();
                });
            });
            
            // Filter functionality
            const projectFilter = document.getElementById('projectFilter');
            const assigneeFilter = document.getElementById('assigneeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const taskRows = document.querySelectorAll('#tasksTableBody tr');
            
            function filterTasks() {
                const projectValue = projectFilter.value;
                const assigneeValue = assigneeFilter.value;
                const statusValue = statusFilter.value;
                
                taskRows.forEach(row => {
                    const projectId = row.getAttribute('data-project-id');
                    const status = row.getAttribute('data-status');
                    const assigneeIds = row.getAttribute('data-assignee-ids').split(',').filter(id => id.trim() !== '');
                    
                    const projectMatch = projectValue === '' || projectId === projectValue;
                    const statusMatch = statusValue === '' || status === statusValue;
                    const assigneeMatch = assigneeValue === '' || assigneeIds.includes(assigneeValue);
                    
                    if (projectMatch && assigneeMatch && statusMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            projectFilter.addEventListener('change', filterTasks);
            assigneeFilter.addEventListener('change', filterTasks);
            statusFilter.addEventListener('change', filterTasks);
        });
    </script>
</body>
</html>