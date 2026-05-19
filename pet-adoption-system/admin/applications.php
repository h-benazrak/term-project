<?php
require_once '../config/database.php';

// Check if user is logged in and is admin or shelter
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

include '../includes/header.php';

// Handle approve/reject actions via POST (secure)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $action = $_POST['action'];
    $reviewed_by = $_SESSION['user_id'];
    
    // Verify shelter has permission to review this application (if not admin)
    if(!isAdmin()) {
        $check = $pdo->prepare("
            SELECT a.id FROM applications a
            JOIN pets p ON a.pet_id = p.id
            WHERE a.id = ? AND p.shelter_id = ?
        ");
        $check->execute([$application_id, $_SESSION['user_id']]);
        if(!$check->fetch()) {
            $_SESSION['error'] = "You don't have permission to review this application.";
            redirect('applications.php');
        }
    }
    
    if($action === 'approve') {
        // Update application status
        $stmt = $pdo->prepare("UPDATE applications SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$reviewed_by, $application_id]);
        
        // Update pet status to 'adopted'
        $pet_stmt = $pdo->prepare("UPDATE pets SET status = 'adopted' WHERE id = (SELECT pet_id FROM applications WHERE id = ?)");
        $pet_stmt->execute([$application_id]);
        
        $_SESSION['success'] = "Application approved successfully. Pet marked as adopted.";
    } 
    elseif($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$reviewed_by, $application_id]);
        
        // Optionally set pet back to available if it was pending from this application
        $pet_stmt = $pdo->prepare("UPDATE pets SET status = 'available' WHERE id = (SELECT pet_id FROM applications WHERE id = ?) AND status = 'pending'");
        $pet_stmt->execute([$application_id]);
        
        $_SESSION['success'] = "Application rejected.";
    }
    redirect('applications.php');
}

// Filter logic
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on user role
if(isAdmin()) {
    $query = "
        SELECT a.*, 
               p.name as pet_name, 
               p.type as pet_type,
               p.image_url,
               u.full_name as adopter_name,
               u.email as adopter_email,
               u.phone as adopter_phone,
               s.full_name as shelter_name,
               r.full_name as reviewed_by_name
        FROM applications a
        JOIN pets p ON a.pet_id = p.id
        JOIN users u ON a.adopter_id = u.id
        JOIN users s ON p.shelter_id = s.id
        LEFT JOIN users r ON a.reviewed_by = r.id
        WHERE 1=1
    ";
    $params = [];
} else {
    $shelter_id = $_SESSION['user_id'];
    $query = "
        SELECT a.*, 
               p.name as pet_name, 
               p.type as pet_type,
               p.image_url,
               u.full_name as adopter_name,
               u.email as adopter_email,
               u.phone as adopter_phone,
               r.full_name as reviewed_by_name
        FROM applications a
        JOIN pets p ON a.pet_id = p.id
        JOIN users u ON a.adopter_id = u.id
        LEFT JOIN users r ON a.reviewed_by = r.id
        WHERE p.shelter_id = ?
    ";
    $params = [$shelter_id];
}

// Apply status filter
if($status_filter !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

// Apply search filter (pet name or adopter name)
if(!empty($search)) {
    $query .= " AND (p.name LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Order by most recent first
$query .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Adoption Applications</h2>
        <hr>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Applications</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Pet name or adopter name" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="w-100">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
            <div class="col-md-12">
                <a href="applications.php" class="btn btn-sm btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Applications List -->
<?php if(count($applications) > 0): ?>
    <div class="row">
        <?php foreach($applications as $app): ?>
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <strong>Application #<?php echo $app['id']; ?></strong>
                            <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?> ms-2">
                                <?php echo strtoupper($app['status']); ?>
                            </span>
                        </div>
                        <small class="text-muted">Submitted: <?php echo date('F d, Y g:i A', strtotime($app['created_at'])); ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <?php if($app['image_url']): ?>
                                    <img src="../<?php echo $app['image_url']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($app['pet_name']); ?>" style="max-height: 150px; width: auto;">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/150x150?text=No+Image" class="img-fluid rounded" alt="No Image">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <h5><?php echo htmlspecialchars($app['pet_name']); ?></h5>
                                <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst($app['pet_type']); ?></p>
                                <?php if(isAdmin() && isset($app['shelter_name'])): ?>
                                    <p class="mb-1"><strong>Shelter:</strong> <?php echo htmlspecialchars($app['shelter_name']); ?></p>
                                <?php endif; ?>
                                <p class="mb-1"><strong>Applicant:</strong> <?php echo htmlspecialchars($app['adopter_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($app['adopter_email']); ?></p>
                                <?php if($app['adopter_phone']): ?>
                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($app['adopter_phone']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <strong>Applicant's Message:</strong>
                                    <p class="text-muted small bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($app['message'] ?: 'No message provided.')); ?></p>
                                </div>
                                
                                <?php if($app['status'] == 'pending'): ?>
                                    <div class="d-grid gap-2">
                                        <form method="POST" onsubmit="return confirm('Approve this application? The pet will be marked as adopted.');">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success w-100 mb-2">Approve Application</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Reject this application?');">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger w-100">Reject Application</button>
                                        </form>
                                    </div>
                                <?php elseif($app['status'] == 'approved'): ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle"></i> Approved by: <?php echo htmlspecialchars($app['reviewed_by_name'] ?? 'System'); ?><br>
                                        <small>on <?php echo date('F d, Y', strtotime($app['reviewed_at'])); ?></small>
                                    </div>
                                <?php elseif($app['status'] == 'rejected'): ?>
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-times-circle"></i> Rejected by: <?php echo htmlspecialchars($app['reviewed_by_name'] ?? 'System'); ?><br>
                                        <small>on <?php echo date('F d, Y', strtotime($app['reviewed_at'])); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle fa-2x mb-3"></i>
        <h4>No applications found</h4>
        <p>There are no adoption applications matching your filters.</p>
        <a href="applications.php" class="btn btn-primary">Clear Filters</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>