<?php
require_once 'config/database.php';

// Only logged-in adopters can access this page
if(!isLoggedIn() || !isAdopter()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch all applications by this adopter with pet and shelter details
$query = "SELECT a.*, 
                 p.name as pet_name, 
                 p.type as pet_type,
                 p.breed,
                 p.image_url,
                 u.full_name as shelter_name,
                 u.email as shelter_email,
                 u.phone as shelter_phone
          FROM applications a 
          JOIN pets p ON a.pet_id = p.id 
          JOIN users u ON p.shelter_id = u.id 
          WHERE a.adopter_id = ? 
          ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>My Adoption Applications</h2>
        <p class="text-muted">Track the status of your adoption requests</p>
        <hr>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(count($applications) > 0): ?>
    <div class="row">
        <?php foreach($applications as $app): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <?php if($app['image_url']): ?>
                                <img src="<?php echo $app['image_url']; ?>" class="img-fluid rounded-start h-100" alt="<?php echo htmlspecialchars($app['pet_name']); ?>" style="object-fit: cover;">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/200x200?text=Pet" class="img-fluid rounded-start h-100" alt="Pet Image" style="object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?php echo htmlspecialchars($app['pet_name']); ?></h5>
                                    <span class="badge <?php 
                                        echo $app['status'] == 'approved' ? 'bg-success' : ($app['status'] == 'rejected' ? 'bg-danger' : 'bg-warning'); 
                                    ?>">
                                        <?php echo strtoupper($app['status']); ?>
                                    </span>
                                </div>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-paw"></i> <?php echo ucfirst($app['pet_type']); ?> | 
                                        <i class="fas fa-dog"></i> <?php echo htmlspecialchars($app['breed']); ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <strong>Shelter:</strong> <?php echo htmlspecialchars($app['shelter_name']); ?><br>
                                    <strong>Applied on:</strong> <?php echo date('F d, Y', strtotime($app['created_at'])); ?>
                                </p>
                                
                                <?php if($app['message']): ?>
                                    <p class="card-text">
                                        <strong>Your Message:</strong><br>
                                        <em><?php echo nl2br(htmlspecialchars(substr($app['message'], 0, 100))); ?></em>
                                        <?php if(strlen($app['message']) > 100): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if($app['status'] == 'approved'): ?>
                                    <div class="alert alert-success mt-2">
                                        <i class="fas fa-check-circle"></i> Congratulations! Your application has been approved. 
                                        Please contact the shelter to complete the adoption process.
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#contactModal<?php echo $app['id']; ?>">
                                            <i class="fas fa-envelope"></i> Contact Shelter
                                        </button>
                                    </div>
                                <?php elseif($app['status'] == 'rejected'): ?>
                                    <div class="alert alert-danger mt-2">
                                        <i class="fas fa-times-circle"></i> Your application was not approved at this time.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-clock"></i> Your application is under review. You will be notified when a decision is made.
                                    </div>
                                <?php endif; ?>
                                
                                <a href="pet-details.php?id=<?php echo $app['pet_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-eye"></i> View Pet Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Shelter Modal for Approved Applications -->
            <?php if($app['status'] == 'approved'): ?>
            <div class="modal fade" id="contactModal<?php echo $app['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Contact Shelter</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Shelter:</strong> <?php echo htmlspecialchars($app['shelter_name']); ?></p>
                            <p><strong>Email:</strong> <a href="mailto:<?php echo $app['shelter_email']; ?>"><?php echo $app['shelter_email']; ?></a></p>
                            <?php if($app['shelter_phone']): ?>
                                <p><strong>Phone:</strong> <?php echo $app['shelter_phone']; ?></p>
                            <?php endif; ?>
                            <hr>
                            <p>Please contact the shelter directly to arrange a meeting and complete the adoption process for <strong><?php echo htmlspecialchars($app['pet_name']); ?></strong>.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <h4>No applications yet</h4>
                <p>You haven't submitted any adoption applications. Browse available pets and start your adoption journey!</p>
                <a href="pets.php" class="btn btn-primary mt-2">Browse Pets</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>