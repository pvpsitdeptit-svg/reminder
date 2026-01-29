let currentPage = 1;
let currentFilter = 'all';

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadFacultyList();
    loadMessages();
    
    // Setup form submission
    document.getElementById('messageForm').addEventListener('submit', sendMessage);
    
    // Setup character counter
    document.getElementById('message').addEventListener('input', updateCharCount);
    
    // Setup tab switching
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            if (e.target.getAttribute('href') === '#messages') {
                loadMessages();
            }
        });
    });
});

function loadFacultyList() {
    fetch('admin_messaging.php?action=get_faculty_list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('recipientEmail');
                select.innerHTML = '<option value="">Select Faculty Member...</option>';
                
                data.faculty.forEach(faculty => {
                    const option = document.createElement('option');
                    option.value = faculty.faculty_email;
                    option.textContent = `${faculty.name} - ${faculty.department} (${faculty.faculty_email})`;
                    option.dataset.hasToken = faculty.fcm_token ? 'true' : 'false';
                    select.appendChild(option);
                });
            } else {
                showAlert('Error loading faculty list: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading faculty list', 'danger');
        });
}

function sendMessage(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    fetch('admin_messaging.php?action=send_message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Message sent successfully! Delivery status: ' + data.delivery_status, 'success');
            clearForm();
            loadMessages(); // Refresh messages list
        } else {
            showAlert('Error sending message: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error sending message', 'danger');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
    });
}

function loadMessages(page = 1) {
    currentPage = page;
    const url = `admin_messaging.php?action=get_messages&page=${page}&filter=${currentFilter}`;
    
    document.getElementById('messagesList').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
                displayPagination(data.pagination);
            } else {
                showAlert('Error loading messages: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading messages', 'danger');
        });
}

function displayMessages(messages) {
    const container = document.getElementById('messagesList');
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No messages found</div>';
        return;
    }
    
    container.innerHTML = messages.map(msg => `
        <div class="message-card card status-${msg.delivery_status || 'sent'}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="card-title">${escapeHtml(msg.subject)}</h6>
                        <p class="message-preview">${escapeHtml(msg.message)}</p>
                        <div class="message-meta">
                            <i class="bi bi-person"></i> To: ${msg.recipient_name || msg.recipient_email}<br>
                            <i class="bi bi-envelope"></i> ${msg.recipient_email}<br>
                            <i class="bi bi-calendar"></i> ${formatDate(msg.created_at)}<br>
                            ${msg.updated_at !== msg.created_at ? '<i class="bi bi-pencil"></i> Updated: ' + formatDate(msg.updated_at) + '<br>' : ''}
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge delivery-badge bg-${getStatusColor(msg.delivery_status)}">
                            ${msg.delivery_status || 'sent'}
                        </span><br><br>
                        <div class="btn-group-vertical btn-group-sm">
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="showDeliveryStatus('${msg.id}')">
                                <i class="bi bi-info-circle"></i> Status
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="editMessage('${msg.id}')">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteMessage('${msg.id}')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function displayPagination(pagination) {
    const container = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if (pagination.current_page > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadMessages(${pagination.current_page - 1})">Previous</a>
        </li>`;
    }
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        const active = i === pagination.current_page ? 'active' : '';
        html += `<li class="page-item ${active}">
            <a class="page-link" href="#" onclick="loadMessages(${i})">${i}</a>
        </li>`;
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadMessages(${pagination.current_page + 1})">Next</a>
        </li>`;
    }
    
    html += '</ul></nav>';
    container.innerHTML = html;
}

function editMessage(messageId) {
    fetch(`admin_messaging.php?action=get_messages&page=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.messages.find(msg => msg.id == messageId);
                if (message) {
                    document.getElementById('editMessageId').value = message.id;
                    document.getElementById('editRecipient').value = message.recipient_email;
                    document.getElementById('editMessageType').value = message.message_type;
                    document.getElementById('editSubject').value = message.subject;
                    document.getElementById('editMessage').value = message.message;
                    
                    new bootstrap.Modal(document.getElementById('editMessageModal')).show();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading message for editing', 'danger');
        });
}

function updateMessage() {
    const formData = new FormData(document.getElementById('editMessageForm'));
    
    fetch('admin_messaging.php?action=edit_message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Message updated successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editMessageModal')).hide();
            loadMessages(currentPage);
        } else {
            showAlert('Error updating message: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating message', 'danger');
    });
}

function deleteMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) {
        return;
    }
    
    fetch('admin_messaging.php?action=delete_message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `message_id=${messageId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Message deleted successfully', 'success');
            loadMessages(currentPage);
        } else {
            showAlert('Error deleting message: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting message', 'danger');
    });
}

function showDeliveryStatus(messageId) {
    fetch(`admin_messaging.php?action=get_delivery_status&message_id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const delivery = data.delivery_status[0];
                const content = `
                    <div class="row">
                        <div class="col-6"><strong>Recipient:</strong></div>
                        <div class="col-6">${delivery.recipient_name || delivery.recipient_email}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Email:</strong></div>
                        <div class="col-6">${delivery.recipient_email}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Subject:</strong></div>
                        <div class="col-6">${delivery.subject}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Status:</strong></div>
                        <div class="col-6">
                            <span class="badge bg-${getStatusColor(delivery.delivery_status)}">
                                ${delivery.delivery_status}
                            </span>
                        </div>
                    </div>
                    ${delivery.sent_at ? `
                    <div class="row">
                        <div class="col-6"><strong>Sent At:</strong></div>
                        <div class="col-6">${formatDate(delivery.sent_at)}</div>
                    </div>
                    ` : ''}
                    ${delivery.delivered_at ? `
                    <div class="row">
                        <div class="col-6"><strong>Delivered At:</strong></div>
                        <div class="col-6">${formatDate(delivery.delivered_at)}</div>
                    </div>
                    ` : ''}
                    ${delivery.error_message ? `
                    <div class="row">
                        <div class="col-6"><strong>Error:</strong></div>
                        <div class="col-6 text-danger">${delivery.error_message}</div>
                    </div>
                    ` : ''}
                `;
                
                document.getElementById('deliveryStatusContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('deliveryModal')).show();
            } else {
                showAlert('Error loading delivery status: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading delivery status', 'danger');
        });
}

function filterMessages(filter) {
    currentFilter = filter;
    loadMessages(1);
}

function clearForm() {
    document.getElementById('messageForm').reset();
    document.getElementById('charCount').textContent = '0';
}

function updateCharCount() {
    const message = document.getElementById('message');
    const charCount = message.value.length;
    const charCountElement = document.getElementById('charCount');
    
    charCountElement.textContent = charCount;
    
    if (charCount > 1000) {
        charCountElement.classList.add('text-danger');
        message.value = message.value.substring(0, 1000);
        charCountElement.textContent = '1000';
    } else {
        charCountElement.classList.remove('text-danger');
    }
}

function refreshMessages() {
    loadMessages(currentPage);
    loadFacultyList();
}

function getStatusColor(status) {
    switch (status) {
        case 'sent': return 'success';
        case 'delivered': return 'info';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('main');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
