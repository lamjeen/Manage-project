<?php
// Modul Tim - Form untuk buat/edit tim

require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: dashboard.php");
    exit;
}

 $team = null;
 $team_members = [];
 $is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $team_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();
    
    if (!$team) {
        header("Location: teams.php");
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT user_id FROM team_members WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team_members = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Form will submit directly to the appropriate handler

 $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
 $users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Team' : 'New Team'; ?> - WeProject</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 5px;
            margin-top: 10px;
            background-color: var(--surface-color);
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Team' : 'New Team'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?php echo $is_edit ? 'team_detail.php?id=' . $team_id : 'teams.php'; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo $is_edit ? 'handle/team/handle_update_team.php' : 'handle/team/handle_create_team.php'; ?>" method="post" enctype="multipart/form-data">
                                    <?php if ($is_edit): ?>
                                        <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Team Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $team['name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $team['description'] ?? ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Team Logo/Avatar</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept=".jpg,.jpeg,.png">
                                        <div class="form-text">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i>
                                                Hanya file JPG atau PNG yang diperbolehkan. Ukuran maksimal 10MB.
                                            </small>
                                        </div>
                                        <?php if (isset($_GET['error'])): ?>
                                            <div class="mt-2">
                                                <?php if ($_GET['error'] == 'invalid_file_type'): ?>
                                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        Tipe file tidak valid. Hanya file JPG dan PNG yang diperbolehkan.
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    </div>
                                                <?php elseif ($_GET['error'] == 'file_too_large'): ?>
                                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        Ukuran file terlalu besar. Maksimal 10MB diperbolehkan.
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($is_edit && !empty($team['logo_path'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Current Logo:</small><br>
                                                <img src="uploads/<?php echo htmlspecialchars($team['logo_path']); ?>" alt="Team Logo" class="logo-preview">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="team_head_id" class="form-label">Team Head</label>
                                            <select class="form-select" id="team_head_id" name="team_head_id">
                                                <option value="">Select Team Head</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo ($team['team_head_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                                        <?php echo $user['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="1" id="make_me_head" name="make_me_head">
                                                <label class="form-check-label" for="make_me_head">
                                                    Make Me Head
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="members-select" class="form-label">Members List</label>
                                        <select id="members-select" name="members[]" multiple placeholder="Select Team Members...">
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $team_members) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="<?php echo $is_edit ? 'team_detail.php?id=' . $team_id : 'teams.php'; ?>" class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Save Changes' : 'Create Team'; ?></button>
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
            new TomSelect('#members-select', {
                plugins: {
                    'checkbox_options': {},
                    'remove_button':{
                        'title':'Remove this item',
                    }
                },
                create: false,
                maxItems: null
            });

            const makeMeHeadCheckbox = document.getElementById('make_me_head');
            const teamHeadDropdown = document.getElementById('team_head_id');
            const currentUserId = '<?php echo $_SESSION['user_id']; ?>';

            makeMeHeadCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    teamHeadDropdown.value = currentUserId;
                } else {
                }
            });
        });
    </script>
</body>
</html>