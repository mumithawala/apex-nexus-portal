<?php
/**
 * Admin Company Detail Page
 */

$pageTitle = "Company Detail - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Get and validate company ID
$companyId = (int)($_GET['id'] ?? 0);
if ($companyId <= 0) {
    setFlash('error', 'Invalid company ID');
    redirect('/apex-nexus-portal/admin/companies.php');
}

// Fetch company details and jobs
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Fetch company details with user info
    $companyStmt = $pdo->prepare("
        SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email as user_email, u.created_at as joined_date
        FROM companies c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.id = ? AND c.is_deleted = 0 AND u.is_deleted = 0
    ");
    $companyStmt->execute([$companyId]);
    $company = $companyStmt->fetch();
    
    if (!$company) {
        setFlash('error', 'Company not found');
        redirect('/apex-nexus-portal/admin/companies.php');
    }
    
    // Fetch company jobs
    $jobsStmt = $pdo->prepare("
        SELECT j.*, 
               (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND is_deleted = 0) as applications_count
        FROM jobs j 
        WHERE j.company_id = ? AND j.is_deleted = 0 
        ORDER BY j.created_at DESC
    ");
    $jobsStmt->execute([$companyId]);
    $jobs = $jobsStmt->fetchAll();
    
    // Calculate totals
    $totalJobs = count($jobs);
    $totalApplications = array_sum(array_column($jobs, 'applications_count'));
    
} catch (PDOException $e) {
    error_log("Company detail error: " . $e->getMessage());
    setFlash('error', 'Failed to load company details');
    redirect('/apex-nexus-portal/admin/companies.php');
}

// Include navbar and sidebar
// require_once '../includes/navbar.php';
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Company Details</h1>
            <p class="text-sm text-gray-500">View company information and job postings</p>
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
        <!-- Back Button -->
        <div class="mb-6">
            <a href="/apex-nexus-portal/admin/companies.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Companies
            </a>
        </div>

        <!-- Company Profile Card -->
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center md:space-x-6">
                    <!-- Company Logo/Avatar -->
                    <div class="flex-shrink-0 mb-4 md:mb-0">
                        <?php if (!empty($company['logo'])): ?>
                            <img src="/apex-nexus-portal/assets/uploads/logos/<?php echo htmlspecialchars($company['logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($company['company_name']); ?>"
                                 class="h-24 w-24 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="h-24 w-24 bg-blue-100 rounded-lg flex items-center justify-center">
                                <span class="text-2xl font-bold text-blue-600">
                                    <?php echo strtoupper(substr($company['company_name'], 0, 2)); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Company Info -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($company['company_name']); ?>
                        </h1>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Contact Person</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($company['user_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($company['user_email']); ?></p>
                            </div>
                            <?php if (!empty($company['website']) && $company['website'] !== 'N/A'): ?>
                                <div>
                                    <p class="text-sm text-gray-500">Website</p>
                                    <p class="text-gray-900">
                                        <?php 
                                        $websiteUrl = (strpos($company['website'], 'http') === 0) ? $company['website'] : 'https://' . $company['website'];
                                        echo '<a href="' . htmlspecialchars($websiteUrl) . '" target="_blank" class="text-blue-600 hover:text-blue-800">' . htmlspecialchars($company['website']) . '</a>';
                                        ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-sm text-gray-500">Joined Date</p>
                                <p class="text-gray-900"><?php echo formatDate($company['joined_date']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($company['description']) && $company['description'] !== 'N/A'): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Description</p>
                                <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo number_format($totalJobs); ?></div>
                        <div class="text-sm text-gray-500">Total Jobs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo number_format($totalApplications); ?></div>
                        <div class="text-sm text-gray-500">Total Applications</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            <?php echo number_format(count(array_filter($jobs, fn($j) => $j['status'] === 'active'))); ?>
                        </div>
                        <div class="text-sm text-gray-500">Active Jobs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">
                            <?php echo number_format(count(array_filter($jobs, fn($j) => $j['status'] === 'pending'))); ?>
                        </div>
                        <div class="text-sm text-gray-500">Pending Jobs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Posted Jobs</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Posted</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($jobs) > 0): ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </div>
                                        <?php if (!empty($job['location'])): ?>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($job['location']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status = $job['status'] ?? 'pending';
                                        $statusClass = match($status) {
                                            'active' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'closed' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo number_format($job['applications_count']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($job['created_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No jobs posted</p>
                                        <p class="text-sm mt-1">This company hasn't posted any jobs yet</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
