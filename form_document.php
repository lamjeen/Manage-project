<?php
// Modul Dokumen - Form untuk buat/edit dokumen

require_once 'auth_check.php';
require_once 'db_connect.php';

 $document = null;
 $is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $document_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        header("Location: projects.php");
        exit;
    }

    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $document['uploaded_by_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }
}

// Form will submit directly to the appropriate handler

 $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
 $projects = $stmt->fetchAll();

 $stmt = $pdo->query("SELECT id, title FROM tasks ORDER BY title");
 $tasks = $stmt->fetchAll();

 $current_project_id = null;
 $current_task_id = null;

if ($is_edit) {
    $current_project_id = $document['project_id'];
    $current_task_id = $document['task_id'];
} else {
    $current_project_id = $_GET['project_id'] ?? null;
    $current_task_id = $_GET['task_id'] ?? null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Document' : 'Upload New Document'; ?> - WeProject</title>
    
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Document' : 'Upload New Document'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?php
                                if ($current_task_id) {
                                    echo 'task_detail.php?id=' . $current_task_id;
                                } elseif ($current_project_id) {
                                    echo 'project_detail.php?id=' . $current_project_id;
                                } else {
                                    echo 'projects.php';
                                }
                            ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo $is_edit ? 'handle/document/handle_update_document.php' : 'handle/document/handle_create_document.php'; ?>" method="post" enctype="multipart/form-data">
                                    <?php if ($is_edit): ?>
                                        <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Document Title</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $document['title'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="file" class="form-label">Select File</label>
                                        <input type="file" class="form-control" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png,.gif" <?php echo !$is_edit ? 'required' : ''; ?>>
                                        <div class="form-text">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i>
                                                File yang diperbolehkan: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, RAR, JPG, JPEG, PNG, GIF.
                                                Ukuran maksimal 50MB.
                                            </small>
                                        </div>
                                        <?php if (isset($_GET['error'])): ?>
                                            <div class="mt-2">
                                                <?php if ($_GET['error'] == 'invalid_file_type'): ?>
                                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        Tipe file tidak valid. File yang diperbolehkan: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, RAR, JPG, JPEG, PNG, GIF.
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    </div>
                                                <?php elseif ($_GET['error'] == 'file_too_large'): ?>
                                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        Ukuran file terlalu besar. Maksimal 50MB diperbolehkan.
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($is_edit && $document['file_name']): ?>
                                            <div class="form-text">Current file: <?php echo $document['file_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $document['description'] ?? ''; ?></textarea>
                                    </div>
                                    <?php if ($current_task_id): ?>
                                        <input type="hidden" name="related_to" value="task">
                                        <input type="hidden" name="task_id" value="<?php echo $current_task_id; ?>">
                                    <?php elseif ($current_project_id): ?>
                                        <input type="hidden" name="related_to" value="project">
                                        <input type="hidden" name="project_id" value="<?php echo $current_project_id; ?>">
                                    <?php endif; ?>
                                    <div class="row">

                                        <div class="col-md-6 mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select" id="category" name="category">
                                                <option value="Design" <?php echo ($document['category'] ?? '') == 'Design' ? 'selected' : ''; ?>>Design</option>
                                                <option value="Document" <?php echo ($document['category'] ?? '') == 'Document' ? 'selected' : ''; ?>>Document</option>
                                                <option value="Report" <?php echo ($document['category'] ?? '') == 'Report' ? 'selected' : ''; ?>>Report</option>
                                                <option value="Other" <?php echo ($document['category'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="uploaded_by" class="form-label">Uploaded By</label>
                                        <input type="text" class="form-control" id="uploaded_by" name="uploaded_by" value="<?php echo $_SESSION['user_name']; ?>" readonly>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="<?php
                                            if ($current_task_id) {
                                                echo 'task_detail.php?id=' . $current_task_id;
                                            } elseif ($current_project_id) {
                                                echo 'project_detail.php?id=' . $current_project_id;
                                            } else {
                                                echo 'projects.php';
                                            }
                                        ?>" class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Save Changes' : 'Upload Document'; ?></button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>