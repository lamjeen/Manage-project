<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Gabungan Modul Projek, Modul Tim, Modul User, dan Modul Tugas

// query untuk menghitung jumlah project dengan status ACTIVE
if ($_SESSION['user_role'] == 'ADMIN') {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM projects WHERE status = 'ACTIVE'");
    $projects_count = $stmt->fetchColumn();
} else {
    $user_id_escaped = $pdo->quote($_SESSION['user_id']);
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) as count 
        FROM projects p 
        LEFT JOIN project_team pt ON p.id = pt.project_id
        LEFT JOIN team_members tm ON pt.team_id = tm.team_id
        WHERE p.status = 'ACTIVE' 
        AND p.end_date IS NOT NULL 
        AND (p.manager_id = $user_id_escaped OR tm.user_id = $user_id_escaped)
    ");
    $projects_count = $stmt->fetchColumn();
}

// query untuk menghitung total tim
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM teams");
 $teams_count = $stmt->fetch()['total'];

// query untuk menghitung total user
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
 $users_count = $stmt->fetch()['total'];

// query untuk mengambil proyek yang deadlinenya akan datang
// filter: hanya project yang user terlibat (sebagai manager atau melalui team)
// Admin bisa lihat semua project
if ($_SESSION['user_role'] == 'ADMIN') {
    $stmt = $pdo->query("SELECT p.*, u.name as manager_name FROM projects p LEFT JOIN users u ON p.manager_id = u.id WHERE p.status = 'ACTIVE' AND p.end_date IS NOT NULL ORDER BY p.end_date ASC LIMIT 5");
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as manager_name 
        FROM projects p 
        LEFT JOIN users u ON p.manager_id = u.id 
        LEFT JOIN project_team pt ON p.id = pt.project_id
        LEFT JOIN team_members tm ON pt.team_id = tm.team_id
        WHERE p.status = 'ACTIVE' 
        AND p.end_date IS NOT NULL 
        AND (p.manager_id = ? OR tm.user_id = ?)
        ORDER BY p.end_date ASC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
}
 $recent_projects = $stmt->fetchAll();

// query untuk menghitung total tugas user saat ini
 $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE assignee = ? AND status != 'DONE'");
 $stmt->execute([$_SESSION['user_id']]);
 $my_tasks_count = $stmt->fetch()['total'];

// query untuk mengambil tugas user saat ini
 $stmt = $pdo->prepare("
     SELECT t.*, p.name as project_name 
     FROM tasks t 
     INNER JOIN projects p ON t.project_id = p.id 
     WHERE t.assignee = ? 
     AND t.status != 'DONE'
     AND p.status = 'ACTIVE'
     ORDER BY t.due_date ASC 
     LIMIT 5
 ");
 $stmt->execute([$_SESSION['user_id']]);
 $my_tasks = $stmt->fetchAll();

// query untuk mengecek deadline H-1 untuk notifikasi
 $stmt = $pdo->prepare("SELECT t.*, p.name as project_name FROM tasks t LEFT JOIN projects p ON t.project_id = p.id WHERE t.assignee = ? AND t.status != 'DONE' AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND t.due_date >= CURDATE()");
 $stmt->execute([$_SESSION['user_id']]);
 $deadline_tasks = $stmt->fetchAll();

// query untuk mengecek deadline H-1 untuk notifikasi proyek
// filter: hanya project yang user terlibat
// admin bisa lihat semua project
if ($_SESSION['user_role'] == 'ADMIN') {
    $stmt = $pdo->query("SELECT p.* FROM projects p WHERE p.status IN ('PLANNING', 'ACTIVE', 'ON_HOLD') AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND p.end_date >= CURDATE()");
} else {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM projects p 
        LEFT JOIN project_team pt ON p.id = pt.project_id
        LEFT JOIN team_members tm ON pt.team_id = tm.team_id
        WHERE p.status IN ('PLANNING', 'ACTIVE', 'ON_HOLD') 
        AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) 
        AND p.end_date >= CURDATE()
        AND (p.manager_id = ? OR tm.user_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
}
 $deadline_projects = $stmt->fetchAll();

// query untuk statistik distribusi tasks berdasarkan status menggunakan COUNT dengan GROUP BY
if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER') {
    $selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    
    $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE manager_id = ? AND status = 'ACTIVE' ORDER BY name");
    $stmt->execute([$_SESSION['user_id']]);
    $managed_projects = $stmt->fetchAll();
    
    if ($selected_project_id && $selected_project_id > 0) {
        $stmt = $pdo->prepare("
        SELECT t.status, COUNT(*) as count FROM tasks t 
        INNER JOIN projects p ON t.project_id = p.id 
        WHERE p.manager_id = ? AND p.status = 'ACTIVE' AND p.id = ? 
        GROUP BY t.status
        ");
        $stmt->execute([$_SESSION['user_id'], $selected_project_id]);
    } else {
        $stmt = $pdo->prepare("SELECT t.status, COUNT(*) as count FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE p.manager_id = ? AND p.status = 'ACTIVE' GROUP BY t.status");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $task_status_stats = $stmt->fetchAll();
} else {
    $managed_projects = [];
    $task_status_stats = [];
    $selected_project_id = null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Manajemen Proyek</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            
            <!-- SIDEBAR START -->
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
            <!-- SIDEBAR END -->
            
            <!-- MAIN CONTENT START -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_project.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus-circle me-1"></i> New Project
                            </a>
                        </div>
                    </div>
                </div>

                <!-- MODUL STATISTIK - Gabungan dari Modul Projek, Tim, User, dan Tugas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Active Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $projects_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-folder-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Teams</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $teams_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-diagram-3-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        
                    <!-- Modul My Total Tasks -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            My Total Tasks</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $my_tasks_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-check-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    
                    <!-- Modul Projects -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Upcoming Project Due</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_projects)): ?>
                                    <p>No active projects with deadlines.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Project Name</th>
                                                    <th>Manager</th>
                                                    <th>Status</th>
                                                    <th>Deadline</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_projects as $project): ?>
                                                <tr>
                                                    <td><a href="project_detail.php?id=<?php echo $project['id']; ?>"><?php echo $project['name']; ?></a></td>
                                                    <td><?php echo $project['manager_name'] ?? 'None'; ?></td>
                                                    <td>
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
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($project['end_date'])); ?></td>
                                                    <td>
                                                        <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Statistik Distribusi Tasks berdasarkan Status (Admin/Manager Only) -->
                        <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER'): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="bi bi-pie-chart-fill me-2"></i>Tasks Distribution by Status
                                </h6>
                                <form method="GET" action="dashboard.php" class="d-inline">
                                    <select name="project_id" id="project_filter" class="form-select form-select-sm" style="width: auto; display: inline-block; min-width: 200px;" onchange="this.form.submit()">
                                        <option value="">All Projects</option>
                                        <?php foreach ($managed_projects as $proj): ?>
                                            <option value="<?php echo $proj['id']; ?>" <?php echo (isset($selected_project_id) && $selected_project_id == $proj['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($proj['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body">
                                <?php
                                $status_config = [
                                    'TO_DO' => ['label' => 'To Do', 'color' => 'secondary', 'icon' => 'bi-circle', 'bg' => '#e9ecef'],
                                    'IN_PROGRESS' => ['label' => 'In Progress', 'color' => 'primary', 'icon' => 'bi-arrow-repeat', 'bg' => '#cfe2ff'],
                                    'REVIEW' => ['label' => 'Review', 'color' => 'warning', 'icon' => 'bi-eye', 'bg' => '#fff3cd'],
                                    'DONE' => ['label' => 'Done', 'color' => 'success', 'icon' => 'bi-check-circle', 'bg' => '#d1e7dd']
                                ];
                                
                                $total_tasks = array_sum(array_column($task_status_stats, 'count'));
                                $status_counts = [];
                                foreach ($task_status_stats as $stat) {
                                    $status_counts[$stat['status']] = $stat['count'];
                                }
                                ?>
                                <div class="row g-3">
                                    <?php foreach ($status_config as $status => $config): 
                                        $count = $status_counts[$status] ?? 0;
                                        $percentage = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
                                    ?>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center p-3 rounded shadow-sm border-start border-4 border-<?php echo $config['color']; ?>" style="background-color: <?php echo $config['bg']; ?>;">
                                            <div class="me-3">
                                                <i class="bi <?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>" style="font-size: 2rem;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-semibold text-<?php echo $config['color']; ?>"><?php echo $config['label']; ?></span>
                                                    <span class="badge bg-<?php echo $config['color']; ?> rounded-pill" style="font-size: 0.9rem;"><?php echo $count; ?></span>
                                                </div>
                                                <div class="progress mb-1" style="height: 10px;">
                                                    <div class="progress-bar bg-<?php echo $config['color']; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%;" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bi bi-percent"></i> <?php echo $percentage; ?>% of total
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3 pt-3 border-top text-center">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Total tasks: <strong class="text-primary"><?php echo $total_tasks; ?></strong>
                                        <?php if (isset($selected_project_id) && $selected_project_id): ?>
                                            <?php 
                                            $selected_project_name = '';
                                            foreach ($managed_projects as $proj) {
                                                if ($proj['id'] == $selected_project_id) {
                                                    $selected_project_name = $proj['name'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <span class="text-muted">(Project: <?php echo htmlspecialchars($selected_project_name); ?>)</span>
                                        <?php else: ?>
                                            <span class="text-muted">(All Projects)</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Modul Tasks -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">My Tasks</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($my_tasks)): ?>
                                    <p>You have no tasks.</p>
                                <?php else: ?>
                                    <?php foreach ($my_tasks as $task): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $task['title']; ?></h6>
                                            <p class="card-text small text-muted">
                                                <i class="bi bi-folder"></i> <?php echo $task['project_name']; ?><br>
                                                <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                            </p>
                                            <div class="d-flex justify-content-between">
                                                <span class="badge bg-<?php 
                                                    echo match($task['status']) {
                                                        'TO_DO' => 'secondary',
                                                        'IN_PROGRESS' => 'primary',
                                                        'REVIEW' => 'warning',
                                                        'DONE' => 'success',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $task['status']; ?></span>
                                                <a href="task_detail.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modul Deadline Notification -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($deadline_tasks) || !empty($deadline_projects)): ?>
                let message = "⚠️ Deadline Reminder:\n\n";

                <?php if (!empty($deadline_tasks)): ?>
                    message += "📋 Tasks due soon:\n";
                    <?php foreach ($deadline_tasks as $task): ?>
                        message += "• <?php echo addslashes($task['title']); ?> (<?php echo addslashes($task['project_name']); ?>)\n";
                    <?php endforeach; ?>
                    message += "\n";
                    <?php endif; ?>

                <?php if (!empty($deadline_projects)): ?>
                    message += "📁 Projects due soon:\n";
                    <?php foreach ($deadline_projects as $project): ?>
                        message += "• <?php echo addslashes($project['name']); ?>\n";
                <?php endforeach; ?>
                <?php endif; ?>

                // show notification on page load
                setTimeout(function() {
                alert(message);
                }, 1000);
            <?php endif; ?>
        });
    </script>
</body>
</html>