<?php
require_once '../config/database.php';

// Check if user is logged in and is admin or shelter
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

// Get pet ID from URL
$pet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($pet_id <= 0) {
    redirect('pets.php');
}

// Fetch pet data
if(isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ? AND shelter_id = ?");
    $stmt->execute([$pet_id, $_SESSION['user_id']]);
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ? AND shelter_id = ?");
}
$stmt->execute(isAdmin() ? [$pet_id] : [$pet_id, $_SESSION['user_id']]);
$pet = $stmt->fetch();

if(!$pet) {
    $_SESSION['error'] = "Pet not found or you don't have permission to edit it.";
    redirect('pets.php');
}

include '../includes/header.php';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $breed = trim($_POST['breed']);
    $age = intval($_POST['age']);
    $age_unit = $_POST['age_unit'];
    $size = $_POST['size'];
    $gender = $_POST['gender'];
    $health_status = trim($_POST['health_status']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    // Validate required fields
    $errors = [];
    if(empty($name)) $errors[] = "Pet name is required.";
    if(empty($type)) $errors[] = "Pet type is required.";
    if($age <= 0) $errors[] = "Age must be a positive number.";
    
    // Handle image upload (optional)
    $image_url = $pet['image_url']; // keep existing by default
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $upload_dir = '../assets/uploads/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_url = 'assets/uploads/' . $new_filename;
                // Optionally delete old image file if exists
                if(!empty($pet['image_url']) && file_exists('../' . $pet['image_url'])) {
                    unlink('../' . $pet['image_url']);
                }
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image format. Allowed: jpg, jpeg, png, gif.";
        }
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("UPDATE pets SET name = ?, type = ?, breed = ?, age = ?, age_unit = ?, size = ?, gender = ?, health_status = ?, description = ?, image_url = ?, status = ? WHERE id = ?");
        if($stmt->execute([$name, $type, $breed, $age, $age_unit, $size, $gender, $health_status, $description, $image_url, $status, $pet_id])) {
            $_SESSION['success'] = "Pet updated successfully!";
            redirect('pets.php');
        } else {
            $errors[] = "Database error. Please try again.";
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Pet: <?php echo htmlspecialchars($pet['name']); ?></h2>
        <hr>
    </div>
</div>

<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Edit Pet Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Pet Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($pet['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Pet Type *</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="dog" <?php echo $pet['type'] == 'dog' ? 'selected' : ''; ?>>Dog</option>
                                <option value="cat" <?php echo $pet['type'] == 'cat' ? 'selected' : ''; ?>>Cat</option>
                                <option value="bird" <?php echo $pet['type'] == 'bird' ? 'selected' : ''; ?>>Bird</option>
                                <option value="rabbit" <?php echo $pet['type'] == 'rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                                <option value="other" <?php echo $pet['type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="breed" class="form-label">Breed</label>
                            <input type="text" class="form-control" id="breed" name="breed" value="<?php echo htmlspecialchars($pet['breed']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="age" class="form-label">Age *</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" step="1" value="<?php echo $pet['age']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="age_unit" class="form-label">Unit</label>
                            <select class="form-control" id="age_unit" name="age_unit">
                                <option value="months" <?php echo $pet['age_unit'] == 'months' ? 'selected' : ''; ?>>Months</option>
                                <option value="years" <?php echo $pet['age_unit'] == 'years' ? 'selected' : ''; ?>>Years</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="size" class="form-label">Size</label>
                            <select class="form-control" id="size" name="size">
                                <option value="small" <?php echo $pet['size'] == 'small' ? 'selected' : ''; ?>>Small</option>
                                <option value="medium" <?php echo $pet['size'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="large" <?php echo $pet['size'] == 'large' ? 'selected' : ''; ?>>Large</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="male" <?php echo $pet['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo $pet['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="available" <?php echo $pet['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="pending" <?php echo $pet['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="adopted" <?php echo $pet['status'] == 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="health_status" class="form-label">Health Status</label>
                        <textarea class="form-control" id="health_status" name="health_status" rows="2"><?php echo htmlspecialchars($pet['health_status']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($pet['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Pet Photo</label>
                        <?php if($pet['image_url']): ?>
                            <div class="mb-2">
                                <img src="<?php echo '../' . $pet['image_url']; ?>" width="100" height="100" style="object-fit: cover;" class="rounded">
                                <p class="text-muted small">Current photo</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">Leave empty to keep current photo. Allowed formats: JPG, JPEG, PNG, GIF.</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="pets.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Pet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Helpful Tips</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Update pet information as needed</li>
                    <li>Upload a new photo to replace the existing one</li>
                    <li>Change status to "Adopted" when pet finds a home</li>
                    <li>Keep health status up to date</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>