<?php
// ethioareb/contact.php - Contact Messages with Full Management
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// Mark as Read
// ============================================================
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $id = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Message marked as read.';
    }
}

// ============================================================
// Mark as Replied
// ============================================================
if (isset($_GET['replied']) && is_numeric($_GET['replied'])) {
    $id = (int)$_GET['replied'];
    $stmt = $conn->prepare("UPDATE contact_messages SET is_replied = 1, replied_by = ?, replied_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $_SESSION['admin_id'], $id);
    if ($stmt->execute()) {
        $message = 'Message marked as replied.';
    }
}

// ============================================================
// Delete Message
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Message deleted successfully.';
    }
}

// ============================================================
// Reply via AJAX
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_action'])) {
    $id = (int)$_POST['id'];
    $reply_message = sanitize($_POST['reply_message']);
    
    if (empty($reply_message)) {
        $error = 'Reply message is required.';
    } else {
        $stmt = $conn->prepare("UPDATE contact_messages SET reply_message = ?, is_replied = 1, replied_by = ?, replied_at = NOW() WHERE id = ?");
        $stmt->bind_param("sii", $reply_message, $_SESSION['admin_id'], $id);
        if ($stmt->execute()) {
            $message = 'Reply saved successfully.';
        } else {
            $error = 'Error saving reply.';
        }
    }
}

// ============================================================
// Get All Messages
// ============================================================
$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$unread = getUnreadCount();
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Contact Messages</h2>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600"><?php echo $_SESSION['admin_name']; ?></span>
            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-semibold">
                <?php echo substr($_SESSION['admin_name'] ?? 'A', 0, 1); ?>
            </div>
        </div>
    </div>
    
    <div class="content">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-dark-slate">Messages</h2>
                <p class="text-gray-500 text-sm">Manage contact form submissions</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">Total: <?php echo count($messages); ?></span>
                <?php if ($unread > 0): ?>
                <span class="badge badge-warning">Unread: <?php echo $unread; ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (!empty($messages)): ?>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $index => $msg): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <div class="font-medium"><?php echo htmlspecialchars($msg['name']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($msg['phone'] ?? ''); ?></div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo $msg['email']; ?>" class="text-primary hover:underline"><?php echo $msg['email']; ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($msg['subject'] ?: 'No subject'); ?></td>
                            <td>
                                <span class="text-sm"><?php echo substr($msg['message'], 0, 50); ?>...</span>
                                <button onclick="showMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['message']); ?>')" class="text-primary hover:text-secondary transition text-sm ml-2">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                            <td>
                                <div class="flex flex-col gap-1">
                                    <span class="badge <?php echo $msg['is_read'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                                    </span>
                                    <?php if ($msg['is_replied']): ?>
                                    <span class="badge badge-info">Replied</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm"><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></div>
                                <div class="text-xs text-gray-400"><?php echo timeAgo($msg['created_at']); ?></div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <?php if (!$msg['is_read']): ?>
                                    <a href="?read=<?php echo $msg['id']; ?>" class="btn-success text-xs py-1 px-2 rounded" title="Mark as read">
                                        <i class="fas fa-check"></i> Read
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!$msg['is_replied']): ?>
                                    <button onclick="openReplyModal(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['name']); ?>', '<?php echo addslashes($msg['email']); ?>', '<?php echo addslashes($msg['subject']); ?>')" class="btn-primary text-xs py-1 px-2 rounded" title="Reply">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <?php endif; ?>
                                    <a href="mailto:<?php echo $msg['email']; ?>?subject=Re: <?php echo urlencode($msg['subject'] ?? 'Your Inquiry'); ?>" class="btn-warning text-xs py-1 px-2 rounded" title="Email">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                    <a href="?delete=<?php echo $msg['id']; ?>" class="btn-danger text-xs py-1 px-2 rounded delete-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-envelope-open text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No messages found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div id="messageModal" class="modal-overlay hidden" onclick="closeModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-dark-slate">Full Message</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div id="messageContent" class="text-gray-700 leading-relaxed whitespace-pre-wrap"></div>
        <div class="mt-6 text-right">
            <button onclick="closeModal()" class="btn-danger">Close</button>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="modal-overlay hidden" onclick="closeReplyModal()">
    <div class="modal-box" onclick="event.stopPropagation()" style="max-width: 600px;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-dark-slate">Reply to Message</h3>
            <button onclick="closeReplyModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" id="replyId">
            <input type="hidden" name="reply_action" value="1">
            
            <div class="mb-4">
                <label class="font-semibold text-dark-slate">To:</label>
                <p id="replyTo" class="text-gray-600"></p>
            </div>
            <div class="mb-4">
                <label class="font-semibold text-dark-slate">Subject:</label>
                <p id="replySubject" class="text-gray-600"></p>
            </div>
            <div class="mb-4">
                <label class="font-semibold text-dark-slate">Your Reply <span class="text-red-500">*</span></label>
                <textarea name="reply_message" id="replyMessage" rows="5" required class="form-control" placeholder="Type your reply here..."></textarea>
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
                <button type="button" onclick="closeReplyModal()" class="btn-danger">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showMessage(id, message) {
    document.getElementById('messageContent').textContent = message;
    document.getElementById('messageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('messageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openReplyModal(id, name, email, subject) {
    document.getElementById('replyId').value = id;
    document.getElementById('replyTo').textContent = name + ' <' + email + '>';
    document.getElementById('replySubject').textContent = 'Re: ' + (subject || 'Your Inquiry');
    document.getElementById('replyMessage').value = '';
    document.getElementById('replyModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReplyModal() {
    document.getElementById('replyModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>