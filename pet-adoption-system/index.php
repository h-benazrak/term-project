<?php
require_once 'config/database.php';
include 'includes/header.php';

// Get featured pets (most recent 6 available pets)
$stmt = $pdo->query("SELECT p.*, u.shelter_name 
                     FROM pets p 
                     JOIN users u ON p.shelter_id = u.id 
                     WHERE p.status = 'available' 
                     ORDER BY p.created_at DESC LIMIT 6");
$featured_pets = $stmt->fetchAll();

// Get counts for statistics section
$total_pets = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'available'")->fetchColumn();
$total_shelters = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'shelter'")->fetchColumn();
$total_adoptions = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'")->fetchColumn();
?>

<!-- Hero Section -->
<div class="hero-section text-center py-5 mb-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
    <div class="container">
        <h1 class="display-4">Find Your Perfect Companion</h1>
        <p class="lead mt-3">Thousands of pets are waiting for a loving home. Start your adoption journey today!</p>
        <div class="mt-4">
            <a href="pets.php" class="btn btn-light btn-lg me-2"><i class="fas fa-search"></i> Browse Pets</a>
            <a href="register.php" class="btn btn-outline-light btn-lg"><i class="fas fa-user-plus"></i> Register Now</a>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="row mb-5 text-center">
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <i class="fas fa-paw fa-3x text-primary mb-3"></i>
                <h3><?php echo $total_pets; ?></h3>
                <p class="text-muted">Pets Available</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <i class="fas fa-building fa-3x text-success mb-3"></i>
                <h3><?php echo $total_shelters; ?></h3>
                <p class="text-muted">Partner Shelters</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <i class="fas fa-heart fa-3x text-danger mb-3"></i>
                <h3><?php echo $total_adoptions; ?></h3>
                <p class="text-muted">Happy Adoptions</p>
            </div>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="row mb-5">
    <div class="col-md-12 text-center mb-4">
        <h2>How It Works</h2>
        <p class="text-muted">Three simple steps to find your new best friend</p>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-search fa-2x"></i>
                </div>
                <h5>1. Browse Pets</h5>
                <p class="text-muted">Search through our database of pets available for adoption. Filter by type, breed, size, and more.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
                <h5>2. Apply Online</h5>
                <p class="text-muted">Found your perfect match? Submit an adoption application with your details.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-home fa-2x"></i>
                </div>
                <h5>3. Get Approved</h5>
                <p class="text-muted">Shelters review your application. Once approved, welcome your new family member home!</p>
            </div>
        </div>
    </div>
</div>

<!-- Featured Pets Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Featured Pets</h2>
            <a href="pets.php" class="btn btn-link">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <hr>
    </div>
</div>

<?php if(count($featured_pets) > 0): ?>
    <div class="row">
        <?php foreach($featured_pets as $pet): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if($pet['image_url']): ?>
                        <img src="<?php echo $pet['image_url']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($pet['name']); ?>" style="height: 220px; object-fit: cover;">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/300x220?text=Pet+Image" class="card-img-top" alt="Pet Image" style="height: 220px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($pet['name']); ?></h5>
                        <p class="card-text">
                            <strong>Type:</strong> <?php echo ucfirst($pet['type']); ?><br>
                            <strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed'] ?: 'Mixed'); ?><br>
                            <strong>Age:</strong> <?php echo $pet['age'] . ' ' . $pet['age_unit']; ?><br>
                            <strong>Shelter:</strong> <?php echo htmlspecialchars($pet['shelter_name']); ?>
                        </p>
                        <a href="pet-details.php?id=<?php echo $pet['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        <?php if(isLoggedIn() && isAdopter()): ?>
                            <a href="apply-adoption.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-success btn-sm">Adopt Me</a>
                        <?php elseif(!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-outline-secondary btn-sm">Login to Adopt</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle fa-2x mb-2"></i>
        <h5>No pets available yet</h5>
        <p>Check back soon for pets looking for a loving home.</p>
    </div>
<?php endif; ?>

<!-- Call to Action Banner -->
<div class="row mt-5">
    <div class="col-md-12">
        <div class="bg-primary text-white rounded p-5 text-center">
            <h3>Are you a shelter or rescue organization?</h3>
            <p class="lead">Join our platform to list your pets and reach more potential adopters.</p>
            <a href="register.php" class="btn btn-light btn-lg">Register as Shelter</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>