<?php
/**
 * Admin Dashboard
 */

$pageTitle = "Admin Dashboard - Recruitment Portal";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Fetch dashboard statistics
try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get total counts
    $totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE is_deleted = 0")->fetchColumn();
    $totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies WHERE is_deleted = 0")->fetchColumn();
    $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates WHERE is_deleted = 0")->fetchColumn();
    $totalApplications = $pdo->query("SELECT COUNT(*) FROM applications WHERE is_deleted = 0")->fetchColumn();

    // fetch name from users table
    $userName = $_SESSION['user_name'];
    // Get recent jobs with company names
    $recentJobsStmt = $pdo->query("
        SELECT j.*, c.name as company_name 
        FROM jobs j 
        LEFT JOIN companies c ON j.company_id = c.id 
        WHERE j.is_deleted = 0 
        ORDER BY j.created_at DESC 
        LIMIT 5
    ");
    $recentJobs = $recentJobsStmt->fetchAll();

    // Get recent applications with candidate names and job titles
    $recentApplicationsStmt = $pdo->query("
        SELECT a.*, cand.name as candidate_name, j.title as job_title 
        FROM applications a 
        LEFT JOIN candidates cand ON a.candidate_id = cand.id 
        LEFT JOIN jobs j ON a.job_id = j.id 
        WHERE a.is_deleted = 0 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $recentApplications = $recentApplicationsStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    setFlash('error', 'Failed to load dashboard data');
}

// Include admin sidebar
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">

    <!-- Top Header -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">

    <!-- Left Side -->
    <div>
        <h1 class="text-lg sm:text-xl font-semibold text-gray-800">
            Welcome back, <span class="text-blue-600"><?php echo $userName; ?></span> 👋
        </h1>
        <p class="text-sm text-gray-500">Here’s what’s happening today</p>
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
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>

            <!-- Name -->
            <span class="text-sm font-medium text-gray-700 hidden sm:block">
                <?php echo $userName; ?>
            </span>

        </div>

    </div>
</div>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Jobs -->
            <div
                class="bg-gradient-to-r from-blue-900 to-blue-400 text-white p-5 rounded-xl shadow-lg hover:scale-105 transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Total Jobs</p>
                        <h2 class="text-2xl font-bold mt-1"><?php echo $totalJobs; ?></h2>
                    </div>
                    <div class="bg-white/20 p-3 rounded-lg">
                        📄
                    </div>
                </div>
            </div>

            <!-- Companies -->
            <div
                class="bg-gradient-to-r from-purple-900 to-indigo-200 text-white p-5 rounded-xl shadow-lg hover:scale-105 transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Companies</p>
                        <h2 class="text-2xl font-bold mt-1"><?php echo $totalCompanies; ?></h2>
                    </div>
                    <div class="bg-white/20 p-3 rounded-lg">
                        🏢
                    </div>
                </div>
            </div>

            <!-- Candidates -->
            <div
                class="bg-gradient-to-r from-green-900 to-emerald-400 text-white p-5 rounded-xl shadow-lg hover:scale-105 transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Candidates</p>
                        <h2 class="text-2xl font-bold mt-1"><?php echo $totalCandidates; ?></h2>
                    </div>
                    <div class="bg-white/20 p-3 rounded-lg">
                        👤
                    </div>
                </div>
            </div>

            <!-- Applications -->
            <div
                class="bg-gradient-to-r from-orange-900 to-red-400 text-white p-5 rounded-xl shadow-lg hover:scale-105 transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Applications</p>
                        <h2 class="text-2xl font-bold mt-1"><?php echo $totalApplications; ?></h2>
                    </div>
                    <div class="bg-white/20 p-3 rounded-lg">
                        📬
                    </div>
                </div>
            </div>

        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Recent Jobs -->
            <div class="bg-white rounded-xl shadow">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h2 class="font-semibold text-gray-800">Recent Jobs</h2>
                    <a href="/apex-nexus-portal/admin/jobs.php" class="text-blue-600 text-sm">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-2 text-left">Title</th>
                                <th class="px-4 py-2 text-left">Company</th>
                                <th class="px-4 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentJobs as $job): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2"><?php echo $job['title']; ?></td>
                                    <td class="px-4 py-2"><?php echo $job['company_name']; ?></td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs px-2 py-1 rounded bg-gray-100">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="bg-white rounded-xl shadow">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h2 class="font-semibold text-gray-800">Recent Applications</h2>
                    <a href="/apex-nexus-portal/admin/applications.php" class="text-blue-600 text-sm">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-2 text-left">Candidate</th>
                                <th class="px-4 py-2 text-left">Job</th>
                                <th class="px-4 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentApplications as $app): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2"><?php echo $app['candidate_name']; ?></td>
                                    <td class="px-4 py-2"><?php echo $app['job_title']; ?></td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs px-2 py-1 rounded bg-gray-100">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>