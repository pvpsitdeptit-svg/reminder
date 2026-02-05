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
    $day = $_POST['day'] ?? '';
    $time = $_POST['time'] ?? '';
    $name = $_POST['name'] ?? '';
    $faculty_email = $_POST['faculty_email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $room = $_POST['room'] ?? '';
    $template_id = $_POST['template_id'] ?? '';
    
    try {
        if (empty($day) || empty($time) || empty($name) || empty($faculty_email) || empty($subject)) {
            $_SESSION['error_message'] = 'Please fill all required fields';
        } else {
            $templateData = [
                'day' => $day,
                'time' => $time,
                'name' => $name,
                'faculty_email' => $faculty_email,
                'subject' => $subject,
                'room' => $room,
                'created_at' => time(),
                'created_by' => $userEmail
            ];
            
            if ($template_id) {
                // Update existing template
                $database->getReference('lecture_templates/' . $template_id)->update($templateData);
                $_SESSION['success_message'] = 'Lecture template updated successfully!';
            } else {
                // Add new template
                $database->getReference('lecture_templates')->push($templateData);
                $_SESSION['success_message'] = 'Lecture template added successfully!';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error saving template: ' . $e->getMessage();
    }
    
    header('Location: lecture_templates.php');
    exit;
}

// Handle template deletion (before any HTML output)
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $database->getReference('lecture_templates/' . $_GET['delete'])->remove();
        $_SESSION['success_message'] = 'Lecture template deleted successfully!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error deleting template: ' . $e->getMessage();
    }
    
    header('Location: lecture_templates.php');
    exit;
}

require_once 'includes/header.php';

// Fetch lecture templates
$templates = [];
try {
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $all_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];
    
    foreach ($all_templates as $key => $template) {
        $template['id'] = $key;
        $templates[] = $template;
    }
    
    // Sort by day and time
    usort($templates, function($a, $b) {
        $dayOrder = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
        $dayA = $dayOrder[$a['day']] ?? 8;
        $dayB = $dayOrder[$b['day']] ?? 8;
        
        if ($dayA !== $dayB) {
            return $dayA - $dayB;
        }
        
        return strcmp($a['time'] ?? '', $b['time'] ?? '');
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
            <i class="bi bi-journal-plus"></i> Lecture Templates
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
                            <label class="form-label">Day</label>
                            <select name="day" class="form-select" required>
                                <option value="">Select day</option>
                                <option value="Monday" <?php echo ($editingTemplate['day'] ?? '') === 'Monday' ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo ($editingTemplate['day'] ?? '') === 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo ($editingTemplate['day'] ?? '') === 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo ($editingTemplate['day'] ?? '') === 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo ($editingTemplate['day'] ?? '') === 'Friday' ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo ($editingTemplate['day'] ?? '') === 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                                <option value="Sunday" <?php echo ($editingTemplate['day'] ?? '') === 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Time</label>
                            <input type="time" name="time" class="form-control" value="<?php echo h($editingTemplate['time'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Faculty Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo h($editingTemplate['name'] ?? ''); ?>" required>
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
                            <input type="text" name="subject" class="form-control" value="<?php echo h($editingTemplate['subject'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Room</label>
                            <input type="text" name="room" class="form-control" value="<?php echo h($editingTemplate['room'] ?? ''); ?>">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-save"></i> <?php echo $editingTemplate ? 'Update' : 'Save'; ?>
                            </button>
                            <?php if ($editingTemplate): ?>
                                <a href="lecture_templates.php" class="btn btn-secondary">
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
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Lecture Templates</h5>
                    <small><?php echo count($templates); ?> templates</small>
                </div>
                <div class="card-body">
                    <?php if (empty($templates)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-journal-plus text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No Templates</h5>
                            <p class="text-muted">No lecture templates found. Add your first template to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Faculty</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($templates as $template): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary"><?php echo h($template['day']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo h($template['time']); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo h($template['name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo h($template['faculty_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo h($template['subject']); ?></td>
                                            <td><?php echo h($template['room'] ?? 'N/A'); ?></td>
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
