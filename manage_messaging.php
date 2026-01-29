<?php
require_once 'config/firebase.php';

/* ---------------- FORM ACTIONS ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_message') {
        $ref = $database->getReference('admin_messages')->push([
            'sender_email' => $_SESSION['admin_email'] ?? 'admin@system.com',
            'recipient_email' => $_POST['recipient_email'],
            'subject' => $_POST['subject'],
            'message' => $_POST['message'],
            'message_type' => $_POST['message_type'],
            'delivery_status' => 'sent',
            'created_at' => time(),
            'updated_at' => time()
        ]);

        $_SESSION['success_message'] = 'Message sent successfully';
        header('Location: manage_messaging.php');
        exit;
    }

    if ($action === 'edit_message') {
        $database->getReference('admin_messages/' . $_POST['message_id'])->update([
            'subject' => $_POST['subject'],
            'message' => $_POST['message'],
            'message_type' => $_POST['message_type'],
            'updated_at' => time()
        ]);

        $_SESSION['success_message'] = 'Message updated successfully';
        header('Location: manage_messaging.php');
        exit;
    }

    if ($action === 'delete_message') {
        $database->getReference('admin_messages/' . $_POST['message_id'])->remove();
        $_SESSION['success_message'] = 'Message deleted successfully';
        header('Location: manage_messaging.php');
        exit;
    }
}

require_once 'includes/header.php';

/* ---------------- HELPERS ---------------- */
function getBadgeColor($type) {
    return match ($type) {
        'alert' => 'danger',
        'notification' => 'info',
        default => 'secondary'
    };
}

function getStatusBadgeColor($status) {
    return match ($status) {
        'sent' => 'success',
        'delivered' => 'info',
        'failed' => 'danger',
        default => 'secondary'
    };
}

/* ---------------- LOAD DATA ---------------- */
$messages = [];
$faculty = [];

try {
    $msgSnap = $database->getReference('admin_messages')->getSnapshot()->getValue();
    if (is_array($msgSnap)) {
        foreach ($msgSnap as $id => $msg) {
            $msg['id'] = $id;
            $messages[] = $msg;
        }
        usort($messages, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
    }

    $facSnap = $database->getReference('faculty_leave_master')->getSnapshot()->getValue();
    if (is_array($facSnap)) {
        foreach ($facSnap as $f) {
            if (!empty($f['faculty_email'])) {
                $faculty[] = [
                    'faculty_email' => $f['faculty_email'],
                    'name' => $f['name'] ?? '',
                    'department' => $f['department'] ?? ''
                ];
            }
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}
?>
    <div class="container my-4">

<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">
<i class="bi bi-chat-dots"></i> Faculty Messaging
</h1>
<a href="index.php" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back to Dashboard
</a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="alert alert-success"><?= h($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
<div class="alert alert-danger"><?= h($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- SEND -->
<div class="col-lg-4">
<div class="card shadow-soft">
<div class="card-header bg-success text-white">
<strong>Send Message</strong>
</div>

<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="send_message">

<label class="form-label">Recipient</label>
<select class="form-select mb-2" name="recipient_email" required>
<option value="">Select Faculty</option>
<?php foreach ($faculty as $f): ?>
<option value="<?= h($f['faculty_email']); ?>">
<?= h($f['name'].' ('.$f['department'].')'); ?>
</option>
<?php endforeach; ?>
</select>

<label class="form-label">Message Type</label>
<select class="form-select mb-2" name="message_type">
<option value="general">General</option>
<option value="notification">Notification</option>
<option value="alert">Alert</option>
</select>

<label class="form-label">Subject</label>
<input class="form-control mb-2" name="subject" required>

<label class="form-label">Message</label>
<textarea class="form-control mb-3" name="message" rows="4" required></textarea>

<button class="btn btn-success w-100">
<i class="bi bi-send"></i> Send Message
</button>
</form>
</div>
</div>
</div>

<!-- LIST -->
<div class="col-lg-8">
<div class="card shadow-soft">
<div class="card-header bg-primary text-white">
<strong>Sent Messages</strong>
</div>

<div class="card-body p-0">
<?php if (!$messages): ?>
<p class="text-muted text-center py-4">No messages found</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover table-striped table-bordered mb-0 align-middle">
<thead>
<tr>
<th>Subject</th>
<th>Recipient</th>
<th>Type</th>
<th>Status</th>
<th>Date</th>
<th class="text-end">Actions</th>
</tr>
</thead>
<tbody>

<?php foreach ($messages as $m): ?>
<tr>
<td><?= h($m['subject']); ?></td>
<td><?= h($m['recipient_email']); ?></td>
<td><span class="badge bg-<?= getBadgeColor($m['message_type']); ?>">
<?= ucfirst($m['message_type']); ?></span></td>
<td><span class="badge bg-<?= getStatusBadgeColor($m['delivery_status'] ?? 'sent'); ?>">
<?= h($m['delivery_status'] ?? 'sent'); ?></span></td>
<td><?= date('M d, Y h:i A', $m['created_at']); ?></td>
<td class="text-end">
<button class="btn btn-sm btn-outline-primary"
onclick="editMessage('<?= $m['id']; ?>')"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger"
onclick="deleteMessage('<?= $m['id']; ?>')"><i class="bi bi-trash"></i></button>
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

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form method="post">
<input type="hidden" name="action" value="edit_message">
<input type="hidden" name="message_id" id="editId">

<div class="modal-header">
<h5>Edit Message</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<select class="form-select mb-2" name="message_type" id="editType">
<option value="general">General</option>
<option value="notification">Notification</option>
<option value="alert">Alert</option>
</select>

<input class="form-control mb-2" name="subject" id="editSubject" required>
<textarea class="form-control" rows="4" name="message" id="editMessage" required></textarea>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-primary">Update</button>
</div>
</form>
</div>
</div>
</div>

<script>
const messages = <?= json_encode($messages); ?>;

function editMessage(id) {
    const m = messages.find(x => x.id === id);
    if (!m) return;

    editId.value = m.id;
    editType.value = m.message_type;
    editSubject.value = m.subject;
    editMessage.value = m.message;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteMessage(id) {
    if (!confirm('Delete this message?')) return;
    const f = document.createElement('form');
    f.method = 'post';
    f.innerHTML = `
        <input type="hidden" name="action" value="delete_message">
        <input type="hidden" name="message_id" value="${id}">
    `;
    document.body.appendChild(f);
    f.submit();
}
</script>

<?php require_once 'includes/footer.php'; ?>
