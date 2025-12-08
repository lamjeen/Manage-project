<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

 $stmt = $pdo->query("SELECT d.*, u.name as uploader_name, p.name as project_name, t.title as task_title FROM documents d LEFT JOIN users u ON d.uploaded_by_id = u.id LEFT JOIN projects p ON d.project_id = p.id LEFT JOIN tasks t ON d.task_id = t.id ORDER BY d.uploaded_at DESC");
 $documents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - WeProject</title>
    
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
                    <h1 class="h2">Documents</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_document.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-upload me-1"></i> Upload Document
                            </a>
                        </div>
                    </div>
                </div>

                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($documents)): ?>
                            <div class="alert alert-info">No documents yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Document Name</th>
                                            <th>Description</th>
                                            <th>Project</th>
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
                                            <td><?php echo substr($document['description'], 0, 50) . '...'; ?></td>
                                            <td>
                                                <?php if ($document['project_name']): ?>
                                                    <a href="project_detail.php?id=<?php echo $document['project_id']; ?>"><?php echo $document['project_name']; ?></a>
                                                <?php elseif ($document['task_title']): ?>
                                                    <a href="task_detail.php?id=<?php echo $document['task_id']; ?>"><?php echo $document['task_title']; ?></a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $document['category']; ?></td>
                                            <td><?php echo $document['uploader_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($document['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="document_detail.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-info" title="View Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="uploads/<?php echo $document['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Download">
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
                    Are you sure you want to delete this document? This action cannot be undone.
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
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            document.querySelectorAll('.delete-document').forEach(button => {
                button.addEventListener('click', function() {
                    const documentId = this.getAttribute('data-id');
                    confirmDeleteBtn.href = `handle_delete_document.php?id=${documentId}`;
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>