<?php
require_once '../config/database.php';

// Check if user is logged in and is admin or shelter
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

include '../includes/header.php';

// Statistics based on user role
if(isAdmin()) {
    // Admin sees system-wide stats
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_shelters = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'shelter'")->fetchColumn();
    $total_adopters = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'adopter'")->fetchColumn();
    $total_pets = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
    $available_pets = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'available'")->fetchColumn();
    $adopted_pets = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'adopted'")->fetchColumn();
    $pending_pets = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'pending'")->fetchColumn();
    $total_applications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $pending_applications = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
    $approved_applications = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'")->fetchColumn();
    $rejected_applications = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();
} else {
    // Shelter sees only their own stats
    $shelter_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $total_pets = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE shelter_id = ? AND status = 'available'");
    $stmt->execute([$shelter_id]);
    $available_pets = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE shelter_id = ? AND status = 'adopted'");
    $stmt->execute([$shelter_id]);
    $adopted_pets = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE shelter_id = ? AND status = 'pending'");
    $stmt->execute([$shelter_id]);
    $pending_pets = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE p.shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $total_applications = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE a.status = 'pending' AND p.shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $pending_applications = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE a.status = 'approved' AND p.shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $approved_applications = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE a.status = 'rejected' AND p.shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $rejected_applications = $stmt->fetchColumn();
}

// Get recent applications (last 5)
if(isAdmin()) {
    $recent_apps = $pdo->query("
        SELECT a.*, p.name as pet_name, u.full_name as adopter_name 
        FROM applications a 
        JOIN pets p ON a.pet_id = p.id 
        JOIN users u ON a.adopter_id = u.id 
        ORDER BY a.created_at DESC LIMIT 5
    ")->fetchAll();
    
    $recent_pets = $pdo->query("
        SELECT p.*, u.full_name as shelter_name 
        FROM pets p 
        JOIN users u ON p.shelter_id = u.id 
        ORDER BY p.created_at DESC LIMIT 5
    ")->fetchAll();
} else {
    $shelter_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT a.*, p.name as pet_name, u.full_name as adopter_name 
        FROM applications a 
        JOIN pets p ON a.pet_id = p.id 
        JOIN users u ON a.adopter_id = u.id 
        WHERE p.shelter_id = ? 
        ORDER BY a.created_at DESC LIMIT 5
    ");
    $stmt->execute([$shelter_id]);
    $recent_apps = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE shelter_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$shelter_id]);
    $recent_pets = $stmt->fetchAll();
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            <div class="text-muted">
                Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 
                (<?php echo isAdmin() ? 'Administrator' : 'Shelter Staff'; ?>)
            </div>
        </div>
        <hr>
    </div>
</div>

<!-- Welcome Message -->
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle"></i> Welcome to your dashboard! Here you can manage pets, review adoption applications, and track statistics.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Statistics Cards Row 1 - Overview -->
<div class="row mb-4">
    <?php if(isAdmin()): ?>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Users</h6>
                            <h2 class="mb-0"><?php echo $total_users; ?></h2>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                    <small class="text-white-50"><?php echo $total_shelters; ?> Shelters | <?php echo $total_adopters; ?> Adopters</small>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="col-md-<?php echo isAdmin() ? '3' : '4'; ?> mb-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Pets</h6>
                        <h2 class="mb-0"><?php echo $total_pets; ?></h2>
                    </div>
                    <i class="fas fa-paw fa-2x opacity-50"></i>
                </div>
                <small class="text-white-50"><?php echo $available_pets; ?> Available | <?php echo $adopted_pets; ?> Adopted</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-<?php echo isAdmin() ? '3' : '4'; ?> mb-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Applications</h6>
                        <h2 class="mb-0"><?php echo $total_applications; ?></h2>
                    </div>
                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                </div>
                <small class="text-white-50">Total received</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-<?php echo isAdmin() ? '3' : '4'; ?> mb-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Apps</h6>
                        <h2 class="mb-0"><?php echo $pending_applications; ?></h2>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
                <small class="text-white-50">Need review</small>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards Row 2 - Detailed (for admin) -->
<?php if(isAdmin()): ?>
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6>Approved Applications</h6>
                <h3 class="text-info"><?php echo $approved_applications; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6>Rejected Applications</h6>
                <h3 class="text-danger"><?php echo $rejected_applications; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6>Pending Pets</h6>
                <h3 class="text-warning"><?php echo $pending_pets; ?></h3>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Shelter additional stats -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6>Approved Applications</h6>
                <h3 class="text-info"><?php echo $approved_applications; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6>Rejected Applications</h6>
                <h3 class="text-danger"><?php echo $rejected_applications; ?></h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <a href="add-pet.php" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> Add New Pet
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="manage-pets.php" class="btn btn-info w-100">
                            <i class="fas fa-list"></i> Manage Pets
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="applications.php" class="btn btn-warning w-100">
                            <i class="fas fa-file-alt"></i> Review Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity (Two Columns) -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-paw"></i> Recent Pets Added</h5>
            </div>
            <div class="card-body">
                <?php if(count($recent_pets) > 0): ?>
                    <div class="list-group">
                        <?php foreach($recent_pets as $pet): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pet['name']); ?></strong>
                                        <span class="badge bg-secondary"><?php echo ucfirst($pet['type']); ?></span>
                                        <?php if(isAdmin() && isset($pet['shelter_name'])): ?>
                                            <br><small class="text-muted">Shelter: <?php echo htmlspecialchars($pet['shelter_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-<?php echo $pet['status'] == 'available' ? 'success' : ($pet['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($pet['status']); ?>
                                    </span>
                                </div>
                                <small class="text-muted">Added: <?php echo date('M d, Y', strtotime($pet['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="manage-pets.php" class="btn btn-sm btn-outline-success">View All Pets</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No pets added yet. <a href="add-pet.php">Add your first pet</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Recent Applications</h5>
            </div>
            <div class="card-body">
                <?php if(count($recent_apps) > 0): ?>
                    <div class="list-group">
                        <?php foreach($recent_apps as $app): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($app['adopter_name']); ?></strong> applied for 
                                        <strong><?php echo htmlspecialchars($app['pet_name']); ?></strong>
                                    </div>
                                    <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </div>
                                <small class="text-muted">Applied: <?php echo date('M d, Y', strtotime($app['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="applications.php" class="btn btn-sm btn-outline-warning">View All Applications</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No applications received yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>