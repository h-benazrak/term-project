<?php
require_once '../config/database.php';

// Check if user is logged in and is admin or shelter
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

include '../includes/header.php';

// Handle Bulk Delete
if(isset($_POST['bulk_delete']) && isset($_POST['selected_pets']) && is_array($_POST['selected_pets'])) {
    $selected = $_POST['selected_pets'];
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    
    if(isAdmin()) {
        $stmt = $pdo->prepare("DELETE FROM pets WHERE id IN ($placeholders)");
    } else {
        // Shelter can only delete their own pets
        $stmt = $pdo->prepare("DELETE FROM pets WHERE id IN ($placeholders) AND shelter_id = ?");
        $selected[] = $_SESSION['user_id'];
    }
    if($stmt->execute($selected)) {
        $_SESSION['success'] = count($selected) - (isShelter() ? 1 : 0) . " pet(s) deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete pets.";
    }
    redirect('manage-pets.php');
}

// Handle single delete (same as pets.php)
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pet_id = $_GET['delete'];
    if(!isAdmin()) {
        $check = $pdo->prepare("SELECT shelter_id FROM pets WHERE id = ?");
        $check->execute([$pet_id]);
        $pet = $check->fetch();
        if($pet && $pet['shelter_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Permission denied.";
            redirect('manage-pets.php');
        }
    }
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
    if($stmt->execute([$pet_id])) {
        $_SESSION['success'] = "Pet deleted successfully.";
    } else {
        $_SESSION['error'] = "Delete failed.";
    }
    redirect('manage-pets.php');
}

// Filter and search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

if(isAdmin()) {
    $query = "SELECT p.*, u.full_name as shelter_name FROM pets p JOIN users u ON p.shelter_id = u.id WHERE 1=1";
    $params = [];
} else {
    $shelter_id = $_SESSION['user_id'];
    $query = "SELECT p.* FROM pets p WHERE p.shelter_id = ?";
    $params = [$shelter_id];
}

if(!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.breed LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if(!empty($type_filter)) {
    $query .= " AND p.type = ?";
    $params[] = $type_filter;
}
if(!empty($status_filter)) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
}
$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pets = $stmt->fetchAll();

// Fetch distinct types for filter dropdown
$types = $pdo->query("SELECT DISTINCT type FROM pets")->fetchAll();
$statuses = ['available', 'pending', 'adopted'];
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h2>Manage Pets</h2>
            <a href="add-pet.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Pet</a>
        </div>
        <hr>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name or breed" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label>Pet Type</label>
                <select name="type" class="form-control">
                    <option value="">All</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?php echo $t['type']; ?>" <?php echo $type_filter == $t['type'] ? 'selected' : ''; ?>><?php echo ucfirst($t['type']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <?php foreach($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status_filter == $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <div><button type="submit" class="btn btn-primary w-100">Filter</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Pet List with Bulk Actions -->
<form method="POST" id="bulkForm" onsubmit="return confirmBulkDelete();">
    <div class="card">
        <div class="card-body">
            <?php if(count($pets) > 0): ?>
                <div class="mb-3">
                    <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled><i class="fas fa-trash-alt"></i> Delete Selected</button>
                    <span class="text-muted ms-2">Select pets below</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Breed</th>
                                <th>Age</th>
                                <th>Status</th>
                                <?php if(isAdmin()): ?><th>Shelter</th><?php endif; ?>
                                <th>Added</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pets as $pet): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_pets[]" value="<?php echo $pet['id']; ?>" class="petCheckbox"></td>
                                    <td><?php echo $pet['id']; ?></td>
                                    <td>
                                        <?php if($pet['image_url']): ?>
                                            <img src="../<?php echo $pet['image_url']; ?>" width="50" height="50" style="object-fit:cover;" class="rounded">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/50x50?text=No+Img" width="50" height="50" class="rounded">
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
                                        <a href="edit-pet.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <a href="javascript:void(0);" onclick="confirmSingleDelete(<?php echo $pet['id']; ?>)" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                     </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No pets found. <a href="add-pet.php">Add a new pet</a></div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
// Select all checkboxes
document.getElementById('selectAll')?.addEventListener('change', function(e) {
    document.querySelectorAll('.petCheckbox').forEach(cb => cb.checked = e.target.checked);
    toggleBulkDeleteButton();
});

// Enable/disable bulk delete button based on selections
document.querySelectorAll('.petCheckbox').forEach(cb => {
    cb.addEventListener('change', toggleBulkDeleteButton);
});

function toggleBulkDeleteButton() {
    const anyChecked = document.querySelectorAll('.petCheckbox:checked').length > 0;
    document.getElementById('bulkDeleteBtn').disabled = !anyChecked;
}

function confirmSingleDelete(petId) {
    if(confirm('Delete this pet permanently?')) {
        window.location.href = 'manage-pets.php?delete=' + petId;
    }
}

function confirmBulkDelete() {
    let checked = document.querySelectorAll('.petCheckbox:checked');
    if(checked.length === 0) {
        alert('Please select at least one pet.');
        return false;
    }
    return confirm('Delete ' + checked.length + ' pet(s)? This cannot be undone.');
}
</script>

<?php include '../includes/footer.php'; ?>