<?php
require_once 'config/database.php';
include 'includes/header.php';

// Build query with filters
$query = "SELECT p.*, u.shelter_name FROM pets p 
          JOIN users u ON p.shelter_id = u.id 
          WHERE p.status = 'available'";

$params = [];

if(isset($_GET['type']) && !empty($_GET['type'])) {
    $query .= " AND p.type = ?";
    $params[] = $_GET['type'];
}

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $query .= " AND (p.name LIKE ? OR p.breed LIKE ?)";
    $params[] = "%{$_GET['search']}%";
    $params[] = "%{$_GET['search']}%";
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pets = $stmt->fetchAll();

// Get unique pet types for filter
$types = $pdo->query("SELECT DISTINCT type FROM pets WHERE status = 'available'")->fetchAll();
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Filter Pets</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" value="<?php echo $_GET['search'] ?? ''; ?>" placeholder="Search by name or breed">
                    </div>
                    
                    <div class="mb-3">
                        <label>Pet Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <?php foreach($types as $t): ?>
                                <option value="<?php echo $t['type']; ?>" <?php echo (isset($_GET['type']) && $_GET['type'] == $t['type']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($t['type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <a href="pets.php" class="btn btn-secondary w-100 mt-2">Reset</a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <h3>Available Pets (<?php echo count($pets); ?>)</h3>
        <div class="row">
            <?php if(count($pets) > 0): ?>
                <?php foreach($pets as $pet): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if($pet['image_url']): ?>
                                <img src="<?php echo $pet['image_url']; ?>" class="card-img-top" alt="<?php echo $pet['name']; ?>" style="height: 180px; object-fit: cover;">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x180?text=Pet+Image" class="card-img-top" alt="Pet Image" style="height: 180px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($pet['name']); ?></h5>
                                <p class="card-text small">
                                    <strong>Type:</strong> <?php echo ucfirst($pet['type']); ?><br>
                                    <strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?><br>
                                    <strong>Age:</strong> <?php echo $pet['age'] . ' ' . $pet['age_unit']; ?><br>
                                    <strong>Size:</strong> <?php echo ucfirst($pet['size']); ?><br>
                                    <strong>Shelter:</strong> <?php echo htmlspecialchars($pet['shelter_name']); ?>
                                </p>
                                <a href="pet-details.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                <?php if(isLoggedIn() && isAdopter()): ?>
                                    <a href="apply-adoption.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-success">Adopt Me</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No pets found matching your criteria.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>