<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $user_type = $_POST['user_type'];
    $shelter_name = $_POST['shelter_name'] ?? null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, address, user_type, shelter_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $password, $phone, $address, $user_type, $shelter_name]);
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    } catch(PDOException $e) {
        $error = "Email already exists!";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Register for Pet Adoption</h4>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label>User Type *</label>
                        <select name="user_type" id="user_type" class="form-control" required>
                            <option value="adopter">Pet Adopter</option>
                            <option value="shelter">Animal Shelter Staff</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="shelter_name_div" style="display:none;">
                        <label>Shelter Name *</label>
                        <input type="text" name="shelter_name" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                    <a href="login.php" class="btn btn-link">Already have an account? Login</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('user_type').addEventListener('change', function() {
    var shelterDiv = document.getElementById('shelter_name_div');
    if(this.value === 'shelter') {
        shelterDiv.style.display = 'block';
    } else {
        shelterDiv.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>