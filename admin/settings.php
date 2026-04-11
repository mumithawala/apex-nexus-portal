<?php
/**
 * Admin Settings Page
 */

$pageTitle = "Settings - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Fetch current admin info
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        setFlash('error', 'Admin account not found');
        redirect('admin/dashboard.php');
    }
    
} catch (PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load admin data');
    redirect('admin/dashboard.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    
    // Validate inputs
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email is taken by another user
    if (empty($errors) && $email !== $admin['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_deleted = 0");
        $stmt->execute([$email, $admin['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already taken';
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $admin['id']]);
            
            // Update session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            setFlash('success', 'Profile updated successfully');
            redirect('admin/settings.php');
            
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            setFlash('error', 'Failed to update profile');
        }
    } else {
        setFlash('error', implode(', ', $errors));
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    }
    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    // Verify current password
    if (empty($errors) && !password_verify($currentPassword, $admin['password'])) {
        $errors[] = 'Current password is incorrect';
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $admin['id']]);
            
            setFlash('success', 'Password changed successfully');
            redirect('admin/settings.php');
            
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            setFlash('error', 'Failed to change password');
        }
    } else {
        setFlash('error', implode(', ', $errors));
    }
}

// Include navbar and sidebar
require_once '../includes/auth.php';

require_once '../includes/urls.php';
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Settings</h1>
            <p class="text-sm text-gray-500">Manage your admin account settings</p>
        </div>
        
        <!-- Right Side -->
        <div class="flex items-center gap-4">
            <!-- Notification -->
            <button class="relative p-2 rounded-full hover:bg-gray-100 transition">
                🔔
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- Profile -->
            <div class="flex items-center gap-3 bg-gray-100 px-3 py-2 rounded-full cursor-pointer hover:bg-gray-200 transition">
                <!-- Avatar -->
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <!-- Name -->
                <span class="text-sm font-medium text-gray-700 hidden sm:block">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-2xl mx-auto">
            <!-- Update Profile Card -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Update Profile</h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <!-- Name Field -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?php echo htmlspecialchars($admin['name']); ?>"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your full name"
                            >
                        </div>
                        
                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($admin['email']); ?>"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your email address"
                            >
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button 
                                type="submit" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out"
                            >
                                Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="change_password" value="1">
                        
                        <!-- Current Password Field -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Password
                            </label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your current password"
                            >
                        </div>
                        
                        <!-- New Password Field -->
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                required
                                minlength="8"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your new password"
                            >
                            <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                        </div>
                        
                        <!-- Confirm Password Field -->
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm New Password
                            </label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required
                                minlength="8"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Confirm your new password"
                            >
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button 
                                type="submit" 
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-150 ease-in-out"
                            >
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Info Card -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">User ID</p>
                            <p class="font-medium"><?php echo $admin['id']; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Account Type</p>
                            <p class="font-medium">Administrator</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Member Since</p>
                            <p class="font-medium"><?php echo formatDate($_SESSION['joined_date'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
