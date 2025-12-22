<?php
// Modul Projek - Halaman utama daftar projek

require_once 'auth_check.php';
require_once 'db_connect.php';

    
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Filter: hanya project yang user terlibat (sebagai manager atau melalui team)
// Admin bisa lihat semua project
$user_id_escaped = $pdo->quote($_SESSION['user_id']);
if ($_SESSION['user_role'] == 'ADMIN') {
    $sql = "SELECT DISTINCT p.*, u.name as manager_name 
            FROM projects p 
            LEFT JOIN users u ON p.manager_id = u.id";
} else {
    $sql = "SELECT DISTINCT p.*, u.name as manager_name 
            FROM projects p 
            LEFT JOIN users u ON p.manager_id = u.id
            LEFT JOIN project_team pt ON p.id = pt.project_id
            LEFT JOIN team_members tm ON pt.team_id = tm.team_id
            WHERE (p.manager_id = $user_id_escaped OR tm.user_id = $user_id_escaped)";
}


if (!empty($search_keyword)) {
    $search_keyword_escaped = $pdo->quote('%' . $search_keyword . '%');
    if ($_SESSION['user_role'] == 'ADMIN') {
        $sql .= " WHERE p.name LIKE $search_keyword_escaped";
    } else {
        $sql .= " AND p.name LIKE $search_keyword_escaped";
    }
}


if (!empty($status_filter)) {
    $status_filter_escaped = $pdo->quote($status_filter);
    if ($_SESSION['user_role'] == 'ADMIN') {
        if (!empty($search_keyword)) {
            $sql .= " AND p.status = $status_filter_escaped";
        } else {
            $sql .= " WHERE p.status = $status_filter_escaped";
        }
    } else {
        $sql .= " AND p.status = $status_filter_escaped";
    }
}


$sql .= " ORDER BY p.created_at DESC";


$stmt = $pdo->query($sql);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyek - Sistem Manajemen Proyek</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .project-card {
            transition: transform 0.3s;
        }
        .project-card:hover {
            transform: translateY(-5px);
        }
    </style>
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
                    <h1 class="h2">Projects</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_project.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> New Project
                            </a>
                        </div>
                    </div>
                </div>

                
                <form method="GET" action="projects.php" class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search projects..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="PLANNING" <?php echo ($status_filter == 'PLANNING') ? 'selected' : ''; ?>>Planning</option>
                            <option value="ACTIVE" <?php echo ($status_filter == 'ACTIVE') ? 'selected' : ''; ?>>Active</option>
                            <option value="ON_HOLD" <?php echo ($status_filter == 'ON_HOLD') ? 'selected' : ''; ?>>On Hold</option>
                            <option value="COMPLETED" <?php echo ($status_filter == 'COMPLETED') ? 'selected' : ''; ?>>Completed</option>
                            <option value="CANCELLED" <?php echo ($status_filter == 'CANCELLED') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>

                
                <div class="row">
                    <?php if (empty($projects)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">No projects found.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 project-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo $project['name']; ?></h6>
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
                                <div class="card-body">
                                    <p class="card-text"><?php echo substr($project['description'], 0, 100) . (strlen($project['description']) > 100 ? '...' : ''); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo $project['manager_name'] ?? 'None'; ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($project['start_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between">
                                        <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $project['manager_id'] == $_SESSION['user_id']): ?>
                                        <div class="btn-group" role="group">
                                            <a href="form_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="handle/project/handle_delete_project.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.')">
                                                <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
            <!-- MAIN CONTENT END -->
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>