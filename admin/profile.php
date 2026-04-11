<?php
/**
 * Admin Profile Page
 */

$pageTitle = "My Profile - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Fetch admin info and system stats
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Fetch admin info
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        setFlash('error', 'Admin account not found');
        redirect('admin/dashboard.php');
    }
    
    // Fetch system statistics
    $totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE is_deleted = 0")->fetchColumn();
    $totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies WHERE is_deleted = 0")->fetchColumn();
    $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates WHERE is_deleted = 0")->fetchColumn();
    $totalApplications = $pdo->query("SELECT COUNT(*) FROM applications WHERE is_deleted = 0")->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load profile data');
    redirect('admin/dashboard.php');
}

// Include navbar and sidebar
require_once '../includes/auth.php';

require_once '../includes/urls.php';

// require_once '../includes/navbar.php';
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">My Profile</h1>
            <p class="text-sm text-gray-500">Manage your admin profile and settings</p>
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
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header Background -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-32"></div>
                
                <!-- Profile Content -->
                <div class="px-6 pb-6">
                    <!-- Avatar -->
                    <div class="flex justify-center -mt-16">
                        <div class="h-32 w-32 bg-blue-100 rounded-full border-4 border-white shadow-lg flex items-center justify-center">
                            <span class="text-3xl font-bold text-blue-600">
                                <?php echo strtoupper(substr($admin['name'], 0, 2)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Profile Info -->
                    <div class="text-center mt-6">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($admin['name']); ?>
                        </h1>
                        
                        <div class="flex flex-wrap justify-center items-center gap-3 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Administrator
                            </span>
                            
                            <span class="text-gray-500 text-sm">
                                Member since <?php echo formatDate($admin['created_at']); ?>
                            </span>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-gray-600">
                                <svg class="w-4 h-4 inline mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <?php echo htmlspecialchars($admin['email']); ?>
                            </p>
                        </div>
                        
                        <!-- Edit Profile Button -->
                        <div class="mt-6">
                            <a href="<?php echo $ADMIN_URL; ?>/settings.php" 
                               class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- System Statistics -->
                <div class="mt-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 text-center">System Overview</h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Total Jobs -->
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <div class="flex justify-center mb-3">
                                <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($totalJobs); ?></div>
                            <div class="text-sm text-gray-500 mt-1">Total Jobs</div>
                        </div>
                        
                        <!-- Total Companies -->
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <div class="flex justify-center mb-3">
                                <div class="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($totalCompanies); ?></div>
                            <div class="text-sm text-gray-500 mt-1">Companies</div>
                        </div>
                        
                        <!-- Total Candidates -->
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <div class="flex justify-center mb-3">
                                <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($totalCandidates); ?></div>
                            <div class="text-sm text-gray-500 mt-1">Candidates</div>
                        </div>
                        
                        <!-- Total Applications -->
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <div class="flex justify-center mb-3">
                                <div class="h-12 w-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($totalApplications); ?></div>
                            <div class="text-sm text-gray-500 mt-1">Applications</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="mt-8 bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="<?php echo $ADMIN_URL; ?>/jobs.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Manage Jobs</div>
                                <div class="text-sm text-gray-500">Review and approve job postings</div>
                            </div>
                        </a>
                        
                        <a href="<?php echo $ADMIN_URL; ?>/companies.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Manage Companies</div>
                                <div class="text-sm text-gray-500">View registered companies</div>
                            </div>
                        </a>
                        
                        <a href="<?php echo $ADMIN_URL; ?>/candidates.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Manage Candidates</div>
                                <div class="text-sm text-gray-500">View candidate profiles</div>
                            </div>
                        </a>
                        
                        <a href="<?php echo $ADMIN_URL; ?>/applications.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="h-10 w-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">View Applications</div>
                                <div class="text-sm text-gray-500">Review all applications</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
