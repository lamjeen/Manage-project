<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

 $task = null;
 $is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $task_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        header("Location: projects.php");
        exit;
    }

    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $task['created_by_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }

}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];

    $project_id = $_POST['project_id'];
    $assignee = $_POST['assignee'] ?? null;
    
    if ($is_edit) {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ?, project_id = ?, assignee = ? WHERE id = ?");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $project_id, $assignee, $task_id]);

        header("Location: task_detail.php?id=$task_id");
    } else {
        $created_by_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, status, due_date, project_id, created_by_id, assignee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $project_id, $created_by_id, $assignee]);
        $new_task_id = $pdo->lastInsertId();

        header("Location: task_detail.php?id=$new_task_id");
    }
    exit;
}

$form_data = [
    'title' => $_POST['title'] ?? ($task['title'] ?? ''),
    'description' => $_POST['description'] ?? ($task['description'] ?? ''),
    'priority' => $_POST['priority'] ?? ($task['priority'] ?? 'MEDIUM'),
    'status' => $_POST['status'] ?? ($task['status'] ?? ($_GET['status'] ?? 'TO_DO')),
    'due_date' => $_POST['due_date'] ?? ($task['due_date'] ?? ''),
    'project_id' => $_POST['project_id'] ?? ($task['project_id'] ?? ($_GET['project_id'] ?? '')),
    'assignee' => $_POST['assignee'] ?? ($task['assignee'] ?? '')
];

 $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
 $projects = $stmt->fetchAll();

$selected_project_id = $form_data['project_id'];

if ($selected_project_id) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.name 
        FROM users u
        JOIN team_members tm ON u.id = tm.user_id
        JOIN project_team pt ON tm.team_id = pt.team_id
        WHERE pt.project_id = ?
        ORDER BY u.name
    ");
    $stmt->execute([$selected_project_id]);
    $users = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
    $users = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Task' : 'New Task'; ?> - WeProject</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Task' : 'New Task'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="projects.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="form_task.php<?php echo $is_edit ? '?id=' . $task['id'] : ''; ?>" method="post">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Task Title</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="project_id" class="form-label">Project</label>
                                            
                                            <select class="form-select" id="project_id" name="project_id" required onchange="this.form.submit()">
                                                <option value="">Select Project</option>
                                                <?php foreach ($projects as $project): ?>
                                                    <option value="<?php echo $project['id']; ?>" <?php echo $form_data['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                                        <?php echo $project['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="assignees-select" class="form-label">Assignee</label>
                                            <select class="form-select" id="assignee" name="assignee">
                                                <option value="">Select Assignee</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo $form_data['assignee'] == $user['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority" required>
                                                <option value="LOW" <?php echo $form_data['priority'] == 'LOW' ? 'selected' : ''; ?>>Low</option>
                                                <option value="MEDIUM" <?php echo $form_data['priority'] == 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="HIGH" <?php echo $form_data['priority'] == 'HIGH' ? 'selected' : ''; ?>>High</option>
                                                <option value="CRITICAL" <?php echo $form_data['priority'] == 'CRITICAL' ? 'selected' : ''; ?>>Critical</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="TO_DO" <?php echo $form_data['status'] == 'TO_DO' ? 'selected' : ''; ?>>To-Do</option>
                                                <option value="IN_PROGRESS" <?php echo $form_data['status'] == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="REVIEW" <?php echo $form_data['status'] == 'REVIEW' ? 'selected' : ''; ?>>Review</option>
                                                <option value="DONE" <?php echo $form_data['status'] == 'DONE' ? 'selected' : ''; ?>>Done</option>
                                            </select>
                                        </div>
                                    </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="due_date" class="form-label">Due Date</label>
                                            <input type="datetime-local" class="form-control" id="due_date" name="due_date" value="<?php echo $form_data['due_date'] ? date('Y-m-d\TH:i', strtotime($form_data['due_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="projects.php" class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" name="save_task" class="btn btn-primary"><?php echo $is_edit ? 'Save Changes' : 'Create Task'; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {


        });
    </script>
</body>
</html>