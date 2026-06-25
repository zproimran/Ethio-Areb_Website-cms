<?php
// ethioareb/applications.php - Complete Job Applications Management
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// Update Status
// ============================================================
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);
    $allowed = ['new', 'reviewed', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
    
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $message = 'Application status updated to ' . ucfirst($status);
        }
    }
}

// ============================================================
// Delete Application
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get files to delete
    $stmt = $conn->prepare("SELECT resume, photo FROM job_applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    
    if ($app) {
        if ($app['resume']) {
            $filePath = UPLOAD_PATH . 'applications/' . $app['resume'];
            if (file_exists($filePath)) unlink($filePath);
        }
        if ($app['photo']) {
            $filePath = UPLOAD_PATH . 'applications/' . $app['photo'];
            if (file_exists($filePath)) unlink($filePath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM job_applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Application deleted successfully.';
    }
}

// ============================================================
// Get All Applications
// ============================================================
$result = $conn->query("SELECT * FROM job_applications ORDER BY created_at DESC");
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Get status counts
$statusCounts = [];
$statuses = ['new', 'reviewed', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
foreach ($statuses as $status) {
    $result = $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = '$status'");
    $statusCounts[$status] = $result->fetch_assoc()['count'];
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Job Applications</h2>
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
                <h2 class="text-xl font-semibold text-dark-slate">Applications</h2>
                <p class="text-gray-500 text-sm">Manage job applications from candidates</p>
            </div>
            <span class="text-sm text-gray-500">Total: <?php echo count($applications); ?></span>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Status Stats -->
        <div class="grid grid-cols-3 md:grid-cols-7 gap-3 mb-6">
            <?php foreach ($statusCounts as $status => $count): ?>
            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                <div class="text-sm font-semibold <?php echo $status == 'new' ? 'text-yellow-600' : ($status == 'hired' ? 'text-green-600' : ($status == 'rejected' ? 'text-red-600' : 'text-gray-600')); ?>">
                    <?php echo ucfirst($status); ?>
                </div>
                <div class="text-2xl font-bold text-dark-slate"><?php echo $count; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="table-container">
            <?php if (!empty($applications)): ?>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Experience</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $index => $app): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <?php if ($app['photo']): ?>
                                    <img src="<?php echo UPLOAD_URL . 'applications/' . $app['photo']; ?>" alt="" class="w-8 h-8 object-cover rounded-full">
                                    <?php else: ?>
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-400 text-sm"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($app['nationality'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo $app['email']; ?>" class="text-primary hover:underline"><?php echo $app['email']; ?></a>
                                <div class="text-xs text-gray-400"><?php echo $app['phone']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($app['position_applied']); ?></td>
                            <td><?php echo $app['experience_years']; ?> yrs</td>
                            <td>
                                <select onchange="updateStatus(<?php echo $app['id']; ?>, this.value)" class="form-control text-sm py-1 px-2 w-auto">
                                    <option value="new" <?php echo $app['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="reviewed" <?php echo $app['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="shortlisted" <?php echo $app['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                    <option value="interviewed" <?php echo $app['status'] == 'interviewed' ? 'selected' : ''; ?>>Interviewed</option>
                                    <option value="offered" <?php echo $app['status'] == 'offered' ? 'selected' : ''; ?>>Offered</option>
                                    <option value="hired" <?php echo $app['status'] == 'hired' ? 'selected' : ''; ?>>Hired</option>
                                    <option value="rejected" <?php echo $app['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </td>
                            <td>
                                <div class="text-sm"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></div>
                                <div class="text-xs text-gray-400"><?php echo timeAgo($app['created_at']); ?></div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <button onclick="viewApplication(<?php echo $app['id']; ?>)" class="btn-primary text-xs py-1 px-2 rounded" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($app['resume']): ?>
                                    <a href="<?php echo UPLOAD_URL . 'applications/' . $app['resume']; ?>" target="_blank" class="btn-success text-xs py-1 px-2 rounded" title="Download Resume">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $app['id']; ?>" class="btn-danger text-xs py-1 px-2 rounded delete-btn" title="Delete">
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
                <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No applications found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Application Detail Modal -->
<div id="appModal" class="modal-overlay hidden" onclick="closeAppModal()">
    <div class="modal-box" onclick="event.stopPropagation()" style="max-width: 800px;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-dark-slate">Application Details</h3>
            <button onclick="closeAppModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div id="appContent" class="text-gray-700 leading-relaxed">
            <div class="grid grid-cols-2 gap-4">
                <div><strong>Name:</strong> <span id="appName"></span></div>
                <div><strong>Email:</strong> <span id="appEmail"></span></div>
                <div><strong>Phone:</strong> <span id="appPhone"></span></div>
                <div><strong>Position:</strong> <span id="appPosition"></span></div>
                <div><strong>Experience:</strong> <span id="appExperience"></span> years</div>
                <div><strong>Education:</strong> <span id="appEducation"></span></div>
                <div><strong>Skills:</strong> <span id="appSkills"></span></div>
                <div><strong>Languages:</strong> <span id="appLanguages"></span></div>
                <div><strong>Nationality:</strong> <span id="appNationality"></span></div>
                <div><strong>Location:</strong> <span id="appLocation"></span></div>
            </div>
            <div class="mt-4">
                <strong>Cover Letter:</strong>
                <p class="bg-gray-50 p-3 rounded mt-1" id="appCoverLetter"></p>
            </div>
            <div class="mt-4">
                <strong>Certifications:</strong>
                <p class="bg-gray-50 p-3 rounded mt-1" id="appCertifications"></p>
            </div>
            <div class="mt-4">
                <strong>References:</strong>
                <p class="bg-gray-50 p-3 rounded mt-1" id="appReferees"></p>
            </div>
            <?php if ($app['resume']): ?>
            <div class="mt-4">
                <strong>Resume:</strong>
                <a href="<?php echo UPLOAD_URL . 'applications/' . $app['resume']; ?>" target="_blank" class="text-primary hover:underline">Download Resume</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="mt-6 text-right">
            <button onclick="closeAppModal()" class="btn-danger">Close</button>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    window.location.href = '?status=' + status + '&id=' + id;
}

function viewApplication(id) {
    $.ajax({
        url: 'ajax/get_application.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            $('#appName').text(data.first_name + ' ' + data.last_name);
            $('#appEmail').text(data.email);
            $('#appPhone').text(data.phone || 'N/A');
            $('#appPosition').text(data.position_applied);
            $('#appExperience').text(data.experience_years || '0');
            $('#appEducation').text(data.education_level || 'N/A');
            $('#appSkills').text(data.skills || 'N/A');
            $('#appLanguages').text(data.languages || 'N/A');
            $('#appNationality').text(data.nationality || 'N/A');
            $('#appLocation').text(data.current_location || 'N/A');
            $('#appCoverLetter').text(data.cover_letter || 'No cover letter provided');
            $('#appCertifications').text(data.certifications || 'None');
            $('#appReferees').text(data.referees || 'None');
            $('#appModal').removeClass('hidden');
            document.body.style.overflow = 'hidden';
        },
        error: function() {
            alert('Error loading application details.');
        }
    });
}

function closeAppModal() {
    $('#appModal').addClass('hidden');
    document.body.style.overflow = 'auto';
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>