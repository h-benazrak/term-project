<?php
require_once '../config/database.php';

// Check if user is logged in and is admin or shelter
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

include '../includes/header.php';

// Handle delete request
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pet_id = $_GET['delete'];
    
    // Verify ownership if not admin
    if(!isAdmin()) {
        $check = $pdo->prepare("SELECT shelter_id FROM pets WHERE id = ?");
        $check->execute([$pet_id]);
        $pet = $check->fetch();
        if($pet && $pet['shelter_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "You don't have permission to delete this pet.";
            redirect('pets.php');
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
    if($stmt->execute([$pet_id])) {
        $_SESSION['success'] = "Pet deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete pet.";
    }
    redirect('pets.php');
}

// Fetch pets based on role
if(isAdmin()) {
    $query = "SELECT p.*, u.full_name as shelter_name 
              FROM pets p 
              JOIN users u ON p.shelter_id = u.id 
              ORDER BY p.created_at DESC";
    $stmt = $pdo->query($query);
} else {
    $shelter_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE shelter_id = ? ORDER BY created_at DESC");
    $stmt->execute([$shelter_id]);
}
$pets = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Manage Pets</h2>
            <a href="add-pet.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Pet</a>
        </div>
        <hr>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Breed</th>
                                <th>Age</th>
                                <th>Status</th>
                                <?php if(isAdmin()): ?>
                                    <th>Shelter</th>
                                <?php endif; ?>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($pets) > 0): ?>
                                <?php foreach($pets as $pet): ?>
                                    <tr>
                                        <td><?php echo $pet['id']; ?></td>
                                        <td>
                                            <?php if($pet['image_url']): ?>
                                                <img src="../<?php echo $pet['image_url']; ?>" width="50" height="50" style="object-fit: cover;" class="rounded">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/50x50?text=Pet" width="50" height="50" class="rounded">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($pet['name']); ?></td>
                                        <td><?php echo ucfirst($pet['type']); ?></td>
                                        <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                                        <td><?php echo $pet['age'] . ' ' . $pet['age_unit']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $pet['status'] == 'available' ? 'success' : ($pet['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($pet['status']); ?>
                                            </span>
                                        </td>
                                        <?php if(isAdmin()): ?>
                                            <td><?php echo htmlspecialchars($pet['shelter_name']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo date('M d, Y', strtotime($pet['created_at'])); ?></td>
                                        <td>
                                            <a href="edit-pet.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $pet['id']; ?>)" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '10' : '9'; ?>" class="text-center">
                                        No pets found. <a href="add-pet.php">Add your first pet</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(petId) {
    if(confirm('Are you sure you want to delete this pet? This action cannot be undone.')) {
        window.location.href = 'pets.php?delete=' + petId;
    }
}
</script>

<?php include '../includes/footer.php'; ?>