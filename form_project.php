<?php
require_once 'auth_check.php';
require_once 'db_connect.php';
if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: dashboard.php");
    exit;
}

 $project = null;
 $project_team_ids = [];
 $is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $project_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: projects.php");
        exit;
    }

    if ($_SESSION['user_role'] != 'ADMIN' && $project['manager_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT team_id FROM project_team WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project_team_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $manager_id = $_POST['manager_id'];
    $team_ids = $_POST['team_ids'] ?? [];
    
    if ($is_edit) {
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, start_date = ?, end_date = ?, status = ?, priority = ?, manager_id = ? WHERE id = ?");
        $stmt->execute([$name, $description, $start_date, $end_date, $status, $priority, $manager_id, $project_id]);

        $stmt = $pdo->prepare("DELETE FROM project_team WHERE project_id = ?");
        $stmt->execute([$project_id]);

        if (!empty($team_ids)) {
             $stmt = $pdo->prepare("INSERT INTO project_team (project_id, team_id) VALUES (?, ?)");
             foreach ($team_ids as $tid) {
                 $stmt->execute([$project_id, $tid]);
             }
        }
        
        header("Location: project_detail.php?id=$project_id");
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, start_date, end_date, status, priority, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $start_date, $end_date, $status, $priority, $manager_id]);
        
        $project_id = $pdo->lastInsertId();

        if (!empty($team_ids)) {
             $stmt = $pdo->prepare("INSERT INTO project_team (project_id, team_id) VALUES (?, ?)");
             foreach ($team_ids as $tid) {
                 $stmt->execute([$project_id, $tid]);
             }
        }
        
        header("Location: project_detail.php?id=$project_id");
    }
    exit;
}
 $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name");
 $teams = $stmt->fetchAll();

 $stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('ADMIN', 'MANAGER') ORDER BY name");
 $managers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Project' : 'New Project'; ?> - WeProject</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Project' : 'New Project'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?php echo $is_edit ? 'project_detail.php?id=' . $project_id : 'projects.php'; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="form_project.php<?php echo $is_edit ? '?id=' . $project['id'] : ''; ?>" method="post">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Project Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $project['name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $project['description'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $project['start_date'] ?? ''; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $project['end_date'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="PLANNING" <?php echo ($project['status'] ?? '') == 'PLANNING' ? 'selected' : ''; ?>>Planning</option>
                                                <option value="ACTIVE" <?php echo ($project['status'] ?? '') == 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                                <option value="ON_HOLD" <?php echo ($project['status'] ?? '') == 'ON_HOLD' ? 'selected' : ''; ?>>On Hold</option>
                                                <option value="COMPLETED" <?php echo ($project['status'] ?? '') == 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="CANCELLED" <?php echo ($project['status'] ?? '') == 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority" required>
                                                <option value="LOW" <?php echo ($project['priority'] ?? 'MEDIUM') == 'LOW' ? 'selected' : ''; ?>>Low</option>
                                                <option value="MEDIUM" <?php echo ($project['priority'] ?? 'MEDIUM') == 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="HIGH" <?php echo ($project['priority'] ?? 'MEDIUM') == 'HIGH' ? 'selected' : ''; ?>>High</option>
                                                <option value="CRITICAL" <?php echo ($project['priority'] ?? 'MEDIUM') == 'CRITICAL' ? 'selected' : ''; ?>>Critical</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="manager_id" class="form-label">Project Manager</label>
                                        <select class="form-select" id="manager_id" name="manager_id" required>
                                            <option value="">Select Manager</option>
                                            <?php foreach ($managers as $manager): ?>
                                                <option value="<?php echo $manager['id']; ?>" <?php echo ($project['manager_id'] ?? '') == $manager['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $manager['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="team_ids" class="form-label">Select Teams</label>
                                        <select class="form-select" id="team_ids" name="team_ids[]" multiple>
                                            <option value="">Select Teams...</option>
                                            <?php foreach ($teams as $team): ?>
                                                <option value="<?php echo $team['id']; ?>" <?php echo in_array($team['id'], $project_team_ids) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($team['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="<?php echo $is_edit ? 'project_detail.php?id=' . $project_id : 'projects.php'; ?>" class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Save Changes' : 'Create Project'; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tips</h6>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li>Give your project a clear and descriptive name</li>
                                    <li>Set realistic start and end dates</li>
                                    <li>Choose a responsible project manager</li>
                                    <li>Select the teams that will work on this project</li>
                                    <li>Update the project status regularly</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect('#team_ids', {
                plugins: {
                    'checkbox_options': {},
                    'remove_button':{
                        'title':'Remove this item',
                    }
                },
                create: false,
                maxItems: null,
                placeholder: "Select Teams..."
            });
        });
    </script>
</body>
</html>