<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: dashboard.php");
    exit;
}

 $stmt = $pdo->query("SELECT * FROM users ORDER BY name");
 $users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - WeProject</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            <a class="nav-link" href="tasks.php">
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
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-person-gear me-2"></i> Users
                            </a>
                        </li>
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
                    <h1 class="h2">Users</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
                                <i class="bi bi-plus-circle me-1"></i> New User
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch ($_GET['success']) {
                        case 'updated':
                            echo 'User updated successfully!';
                            break;
                        case 'created':
                            echo 'User created successfully!';
                            break;
                        default:
                            echo 'Operation completed successfully!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    $error_msg = urldecode($_GET['error']);
                    switch ($error_msg) {
                        case 'All fields are required.':
                        case 'Passwords do not match!':
                        case 'Please enter a valid email address.':
                        case 'Invalid role selected.':
                        case 'Email already registered!':
                            echo $error_msg;
                            break;
                        case 'empty_fields':
                            echo 'All fields are required.';
                            break;
                        case 'invalid_email':
                            echo 'Please enter a valid email address.';
                            break;
                        case 'invalid_role':
                            echo 'Invalid role selected.';
                            break;
                        case 'email_exists':
                            echo 'Email address is already in use by another user.';
                            break;
                        case 'cannot_delete':
                            echo 'Cannot delete user who manages projects or has created tasks.';
                            break;
                        default:
                            echo htmlspecialchars($error_msg);
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info">No users yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['name']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($user['role']) {
                                                        'ADMIN' => 'danger',
                                                        'MANAGER' => 'warning',
                                                        'MEMBER' => 'info',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $user['role']; ?></span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-user"
                                                        data-id="<?php echo $user['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                        data-role="<?php echo $user['role']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger delete-user" data-id="<?php echo $user['id']; ?>">
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
                    Are you sure you want to delete this user? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="handle_update_user.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="id">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="MEMBER">Member</option>
                                <option value="MANAGER">Manager</option>
                                <option value="ADMIN">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newUserModalLabel">New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="handle_create_user.php" method="post">
                    <div class="modal-body">
                        <div id="newUserError" class="alert alert-danger" style="display: none;"></div>
                        <div class="mb-3">
                            <label for="newUserName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="newUserName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="newUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="newUserEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="newUserRole" class="form-label">Role</label>
                            <select class="form-select" id="newUserRole" name="role" required>
                                <option value="MEMBER">Member</option>
                                <option value="MANAGER">Manager</option>
                                <option value="ADMIN">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newUserPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="newUserPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newUserConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="newUserConfirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            const newUserModal = new bootstrap.Modal(document.getElementById('newUserModal'));
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    confirmDeleteBtn.href = `handle_delete_user.php?id=${userId}`;
                    deleteModal.show();
                });
            });

            document.querySelectorAll('.edit-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const userName = this.getAttribute('data-name');
                    const userEmail = this.getAttribute('data-email');
                    const userRole = this.getAttribute('data-role');

                    document.getElementById('editUserId').value = userId;
                    document.getElementById('editName').value = userName;
                    document.getElementById('editEmail').value = userEmail;
                    document.getElementById('editRole').value = userRole;

                    editUserModal.show();
                });
            });

            // Reset new user modal form when shown
            document.getElementById('newUserModal').addEventListener('show.bs.modal', function() {
                const form = this.querySelector('form');
                form.reset();
                document.getElementById('newUserError').style.display = 'none';
            });
        });
    </script>
</body>
</html>