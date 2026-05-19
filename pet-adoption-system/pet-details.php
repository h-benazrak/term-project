<?php
require_once 'config/database.php';
include 'includes/header.php';

$pet_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT p.*, u.full_name as shelter_name, u.email as shelter_email, u.phone as shelter_phone 
                       FROM pets p 
                       JOIN users u ON p.shelter_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if(!$pet) {
    redirect('pets.php');
}

// Handle bookmark
if(isLoggedIn() && isset($_POST['bookmark'])) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, pet_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $pet_id]);
        $_SESSION['success'] = "Pet saved to bookmarks!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Already bookmarked!";
    }
    redirect("pet-details.php?id=$pet_id");
}
?>

<div class="row">
    <div class="col-md-6">
        <?php if($pet['image_url']): ?>
            <img src="<?php echo $pet['image_url']; ?>" class="img-fluid rounded" alt="<?php echo $pet['name']; ?>">
        <?php else: ?>
            <img src="https://via.placeholder.com/500x400?text=Pet+Image" class="img-fluid rounded" alt="Pet Image">
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h2><?php echo htmlspecialchars($pet['name']); ?></h2>
        <div class="mb-3">
            <span class="badge bg-success"><?php echo ucfirst($pet['status']); ?></span>
            <span class="badge bg-info"><?php echo ucfirst($pet['type']); ?></span>
        </div>
        
        <table class="table table-borderless">
            <tr>
                <th>Breed:</th>
                <td><?php echo htmlspecialchars($pet['breed']); ?></td>
            </tr>
            <tr>
                <th>Age:</th>
                <td><?php echo $pet['age'] . ' ' . $pet['age_unit']; ?></td>
            </tr>
            <tr>
                <th>Size:</th>
                <td><?php echo ucfirst($pet['size']); ?></td>
            </tr>
            <tr>
                <th>Gender:</th>
                <td><?php echo ucfirst($pet['gender']); ?></td>
            </tr>
            <tr>
                <th>Health Status:</th>
                <td><?php echo htmlspecialchars($pet['health_status']); ?></td>
            </tr>
            <tr>
                <th>Shelter:</th>
                <td><?php echo htmlspecialchars($pet['shelter_name']); ?></td>
            </tr>
        </table>
        
        <div class="mb-4">
            <h5>Description:</h5>
            <p><?php echo nl2br(htmlspecialchars($pet['description'])); ?></p>
        </div>
        
        <?php if(isLoggedIn() && isAdopter() && $pet['status'] == 'available'): ?>
            <a href="apply-adoption.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-success btn-lg">Apply for Adoption</a>
        <?php elseif(!isLoggedIn()): ?>
            <a href="login.php" class="btn btn-primary">Login to Apply</a>
        <?php endif; ?>
        
        <?php if(isLoggedIn() && isAdopter()): ?>
            <form method="POST" style="display: inline;">
                <button type="submit" name="bookmark" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-bookmark"></i> Save to Bookmarks
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>