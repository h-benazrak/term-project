<?php
require_once 'config/database.php';

if(!isLoggedIn() || !isAdopter()) {
    redirect('login.php');
}

$pet_id = $_GET['pet_id'] ?? 0;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adopter_id = $_SESSION['user_id'];
    $message = $_POST['message'];
    
    $stmt = $pdo->prepare("INSERT INTO applications (pet_id, adopter_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$pet_id, $adopter_id, $message]);
    
    // Update pet status to pending
    $stmt = $pdo->prepare("UPDATE pets SET status = 'pending' WHERE id = ?");
    $stmt->execute([$pet_id]);
    
    $_SESSION['success'] = "Application submitted successfully!";
    redirect('my-applications.php');
}

// Get pet details
$stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if(!$pet) {
    redirect('pets.php');
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Adoption Application for <?php echo htmlspecialchars($pet['name']); ?></h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Why do you want to adopt this pet?</label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                    <a href="pet-details.php?id=<?php echo $pet_id; ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>