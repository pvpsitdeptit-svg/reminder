<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Require admin role for this page (before any HTML output)
requireAdmin();

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Handle form submission for adding/editing templates (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_name = $_POST['exam_name'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $venue = $_POST['venue'] ?? '';
    $faculty_email = $_POST['faculty_email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $template_id = $_POST['template_id'] ?? '';
    
    try {
        if (empty($exam_name) || empty($date) || empty($time) || empty($venue) || empty($faculty_email)) {
            $_SESSION['error_message'] = 'Please fill all required fields';
        } else {
            $templateData = [
                'exam' => $exam_name,
                'date' => $date,
                'time' => $time,
                'venue' => $venue,
                'faculty_email' => $faculty_email,
                'subject' => $subject,
                'created_at' => time(),
                'created_by' => $userEmail
            ];
            
            if ($template_id) {
                // Update existing template
                $database->getReference('invigilation_templates/' . $template_id)->update($templateData);
                $_SESSION['success_message'] = 'Invigilation template updated successfully!';
            } else {
                // Add new template
                $database->getReference('invigilation_templates')->push($templateData);
                $_SESSION['success_message'] = 'Invigilation template added successfully!';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error saving template: ' . $e->getMessage();
    }
    
    header('Location: invigilation_templates.php');
    exit;
}

// Handle template deletion (before any HTML output)
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $database->getReference('invigilation_templates/' . $_GET['delete'])->remove();
        $_SESSION['success_message'] = 'Invigilation template deleted successfully!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error deleting template: ' . $e->getMessage();
    }
    
    header('Location: invigilation_templates.php');
    exit;
}

require_once 'includes/header.php';

// Fetch invigilation templates
$templates = [];
try {
    $templates_ref = $database->getReference('invigilation_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $all_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];
    
    foreach ($all_templates as $key => $template) {
        $template['id'] = $key;
        $templates[] = $template;
    }
    
    // Sort by date and time
    usort($templates, function($a, $b) {
        return [$a['date'] ?? '', $a['time'] ?? ''] <=> [$b['date'] ?? '', $b['time'] ?? ''];
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading templates: ' . $e->getMessage();
}

// Fetch faculty list
$facultyList = [];
try {
    $faculty_ref = $database->getReference('faculty_leave_master');
    $faculty_snapshot = $faculty_ref->getSnapshot();
    $facultyList = $faculty_snapshot->exists() ? $faculty_snapshot->getValue() : [];
} catch (Exception $e) {
    // Continue without faculty list
}

// Get template for editing
$editingTemplate = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    foreach ($templates as $template) {
        if ($template['id'] === $_GET['edit']) {
            $editingTemplate = $template;
            break;
        }
    }
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-clipboard-plus"></i> Invigilation Templates
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Template Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $editingTemplate ? 'Edit Template' : 'Add Template'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($editingTemplate): ?>
                            <input type="hidden" name="template_id" value="<?php echo $editingTemplate['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Exam Name</label>
                            <input type="text" name="exam_name" class="form-control" value="<?php echo h($editingTemplate['exam'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo h($editingTemplate['date'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Time</label>
                            <input type="time" name="time" class="form-control" value="<?php echo h($editingTemplate['time'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" value="<?php echo h($editingTemplate['venue'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Faculty Email</label>
                            <select name="faculty_email" class="form-select" required>
                                <option value="">Select faculty</option>
                                <?php foreach ($facultyList as $key => $faculty): ?>
                                    <option value="<?php echo h($faculty['faculty_email'] ?? ''); ?>" <?php echo ($editingTemplate['faculty_email'] ?? '') === ($faculty['faculty_email'] ?? '') ? 'selected' : ''; ?>>
                                        <?php echo h($faculty['name'] ?? $faculty['faculty_email'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" value="<?php echo h($editingTemplate['subject'] ?? ''); ?>">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-save"></i> <?php echo $editingTemplate ? 'Update' : 'Save'; ?>
                            </button>
                            <?php if ($editingTemplate): ?>
                                <a href="invigilation_templates.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Templates List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Invigilation Templates</h5>
                    <small><?php echo count($templates); ?> templates</small>
                </div>
                <div class="card-body">
                    <?php if (empty($templates)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-plus text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No Templates</h5>
                            <p class="text-muted">No invigilation templates found. Add your first template to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Exam</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Venue</th>
                                        <th>Faculty</th>
                                        <th>Subject</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($templates as $template): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo h($template['exam']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo date('M j, Y', strtotime($template['date'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo h($template['time']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo h($template['venue']); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php 
                                                    // Find faculty name from email
                                                    $facultyName = $template['faculty_email'];
                                                    foreach ($facultyList as $faculty) {
                                                        if (($faculty['faculty_email'] ?? '') === $template['faculty_email']) {
                                                            $facultyName = $faculty['name'] ?? $template['faculty_email'];
                                                            break;
                                                        }
                                                    }
                                                    echo h($facultyName);
                                                    ?>
                                                    <br><small class="text-muted"><?php echo h($template['faculty_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo h($template['subject'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this template?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
