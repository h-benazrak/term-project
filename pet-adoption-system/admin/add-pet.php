<?php
require_once '../config/database.php';

// Check if user is logged in and is admin or shelter
if(!isLoggedIn() || (!isAdmin() && !isShelter())) {
    redirect('../index.php');
}

include '../includes/header.php';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shelter_id = $_SESSION['user_id'];
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
    
    // Handle image upload
    $image_url = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            // Create uploads directory if not exists
            $upload_dir = '../assets/uploads/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_url = 'assets/uploads/' . $new_filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image format. Allowed: jpg, jpeg, png, gif.";
        }
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO pets (shelter_id, name, type, breed, age, age_unit, size, gender, health_status, description, image_url, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if($stmt->execute([$shelter_id, $name, $type, $breed, $age, $age_unit, $size, $gender, $health_status, $description, $image_url, $status])) {
            $_SESSION['success'] = "Pet added successfully!";
            redirect('pets.php');
        } else {
            $errors[] = "Database error. Please try again.";
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Add New Pet</h2>
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
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Pet Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Pet Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Pet Type *</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="dog">Dog</option>
                                <option value="cat">Cat</option>
                                <option value="bird">Bird</option>
                                <option value="rabbit">Rabbit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="breed" class="form-label">Breed</label>
                            <input type="text" class="form-control" id="breed" name="breed">
                        </div>
                        <div class="col-md-3">
                            <label for="age" class="form-label">Age *</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" step="1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="age_unit" class="form-label">Unit</label>
                            <select class="form-control" id="age_unit" name="age_unit">
                                <option value="months">Months</option>
                                <option value="years" selected>Years</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="size" class="form-label">Size</label>
                            <select class="form-control" id="size" name="size">
                                <option value="small">Small</option>
                                <option value="medium">Medium</option>
                                <option value="large">Large</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="available">Available</option>
                                <option value="pending">Pending</option>
                                <option value="adopted">Adopted</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="health_status" class="form-label">Health Status</label>
                        <textarea class="form-control" id="health_status" name="health_status" rows="2" placeholder="Vaccinated, spayed/neutered, any medical conditions..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Tell potential adopters about this pet's personality, behavior, and special needs..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Pet Photo</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB.</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="pets.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Pet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Tips</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Use a clear, recent photo of the pet</li>
                    <li>Describe the pet's personality honestly</li>
                    <li>Include vaccination and medical history</li>
                    <li>Mention if the pet is good with children or other animals</li>
                    <li>Set status to "Available" to appear in public listings</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>