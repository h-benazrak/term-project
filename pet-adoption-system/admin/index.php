<?php
require_once '../config/database.php';

// Only admin or shelter staff can access
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

include '../includes/header.php';

// Get role-specific statistics
if(isAdmin()) {
    // Admin sees system-wide stats
    $total_pets = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
    $total_applications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $pending_applications = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_shelters = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'shelter'")->fetchColumn();
    
    // Recent applications for admin
    $recent_apps = $pdo->query("
        SELECT a.*, p.name as pet_name, u.full_name as adopter_name 
        FROM applications a 
        JOIN pets p ON a.pet_id = p.id 
        JOIN users u ON a.adopter_id = u.id 
        ORDER BY a.created_at DESC LIMIT 5
    ")->fetchAll();
} else {
    // Shelter sees only their own data
    $shelter_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $total_pets = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a 
                           JOIN pets p ON a.pet_id = p.id 
                           WHERE p.shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $total_applications = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a 
                           JOIN pets p ON a.pet_id = p.id 
                           WHERE p.shelter_id = ? AND a.status = 'pending'");
    $stmt->execute([$shelter_id]);
    $pending_applications = $stmt->fetchColumn();
    
    // Recent applications for this shelter
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
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-tachometer-alt"></i> <?php echo isAdmin() ? 'Administrator' : 'Shelter'; ?> Dashboard</h2>
            <span class="text-muted">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
        </div>
        <hr>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php if(isAdmin()): ?>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h6 class="card-title">Total Users</h6>
                    <h2 class="mb-0"><?php echo $total_users; ?></h2>
                    <small><?php echo $total_shelters; ?> shelters</small>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="col-md-<?php echo isAdmin() ? '3' : '4'; ?> mb-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h6 class="card-title">My Pets</h6>
                <h2 class="mb-0"><?php echo $total_pets; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-<?php echo isAdmin() ? '3' : '4'; ?> mb-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <h6 class="card-title">Total Applications</h6>
                <h2 class="mb-0"><?php echo $total_applications; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-<?php echo isAdmin() ? '3' : '4'; ?> mb-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <h6 class="card-title">Pending Applications</h6>
                <h2 class="mb-0"><?php echo $pending_applications; ?></h2>
            </div>
        </div>
    </div>
</div>

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

<!-- Recent Applications -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Applications</h5>
            </div>
            <div class="card-body">
                <?php if(count($recent_apps) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Applicant</th>
                                    <th>Pet</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_apps as $app): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($app['adopter_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['pet_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($app['status'] == 'pending'): ?>
                                                <a href="applications.php" class="btn btn-sm btn-primary">Review</a>
                                            <?php else: ?>
                                                <span class="text-muted">Reviewed</span>
                                            <?php endif; ?>
                                         </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="applications.php" class="btn btn-sm btn-outline-primary">View All Applications</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No applications received yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>