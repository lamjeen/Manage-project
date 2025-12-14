<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: dashboard.php");
    exit;
}

 $stmt = $pdo->query("SELECT t.*, u.name as team_head_name FROM teams t LEFT JOIN users u ON t.team_head_id = u.id ORDER BY t.name");
 $teams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams - WeProject</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .team-card {
            transition: transform 0.3s;
        }
        .team-card:hover {
            transform: translateY(-5px);
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
                    <h1 class="h2">Teams</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_team.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> New Team
                            </a>
                        </div>
                    </div>
                </div>

                
                <div class="row">
                    <?php if (empty($teams)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">No teams yet.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teams as $team): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 team-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo $team['name']; ?></h6>
                                    <div class="d-flex align-items-center gap-2">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p class="card-text"><?php echo substr($team['description'], 0, 100) . (strlen($team['description']) > 100 ? '...' : ''); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-person-badge"></i> Team Head: <?php echo $team['team_head_name'] ?? 'None'; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?php if (!empty($team['logo_path'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($team['logo_path']); ?>" alt="Team Logo" class="img-thumbnail" style="max-width: 80px; max-height: 80px;">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between">
                                        <a href="team_detail.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER'): ?>
                                        <div class="btn-group" role="group">
                                            <a href="form_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="handle/team/handle_delete_team.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this team? This action cannot be undone.')">
                                                <input type="hidden" name="id" value="<?php echo $team['id']; ?>">
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
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>