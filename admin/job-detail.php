<?php
/**
 * Admin Job Detail Page
 */

$pageTitle = "Job Detail - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Get and validate job ID
$jobId = (int)($_GET['id'] ?? 0);
if ($jobId <= 0) {
    setFlash('error', 'Invalid job ID');
    redirect('/apex-nexus-portal/admin/jobs.php');
}

// Fetch job details and applications
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Fetch job details with company info
    $jobStmt = $pdo->prepare("
        SELECT j.*, c.company_name, c.company_name as company_name
        FROM jobs j 
        LEFT JOIN companies c ON j.company_id = c.id 
        WHERE j.id = ? AND j.is_deleted = 0
    ");
    $jobStmt->execute([$jobId]);
    $job = $jobStmt->fetch();
    
    if (!$job) {
        setFlash('error', 'Job not found');
        redirect('/apex-nexus-portal/admin/jobs.php');
    }
    
    // Fetch job applications
    $applicationsStmt = $pdo->prepare("
        SELECT a.*, u.name as candidate_name, u.email as candidate_email
        FROM applications a 
        LEFT JOIN candidates cand ON a.candidate_id = cand.id 
        LEFT JOIN users u ON cand.user_id = u.id 
        WHERE a.job_id = ? AND a.is_deleted = 0 
        ORDER BY a.created_at DESC
    ");
    $applicationsStmt->execute([$jobId]);
    $applications = $applicationsStmt->fetchAll();
    
    // Calculate totals
    $totalApplications = count($applications);
    $approvedApplications = count(array_filter($applications, fn($a) => $a['status'] === 'approved'));
    $pendingApplications = count(array_filter($applications, fn($a) => $a['status'] === 'pending'));
    
} catch (PDOException $e) {
    error_log("Job detail error: " . $e->getMessage());
    setFlash('error', 'Failed to load job details');
    redirect('/apex-nexus-portal/admin/jobs.php');
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
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Job Details</h1>
            <p class="text-sm text-gray-500">View job information and applications</p>
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
            <a href="/apex-nexus-portal/admin/jobs.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Jobs
            </a>
        </div>

        <!-- Job Details Card -->
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-start md:space-x-6">
                    <!-- Job Info -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 mb-4">
                            <?php echo htmlspecialchars($job['title']); ?>
                        </h1>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Company</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($job['company_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Location</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Category</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($job['category'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Salary</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($job['salary'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                <p class="text-gray-900">
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
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Posted Date</p>
                                <p class="text-gray-900"><?php echo formatDate($job['created_at']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Deadline</p>
                                <p class="text-gray-900"><?php echo formatDate($job['deadline']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($job['description']) && $job['description'] !== 'N/A'): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-2">Job Description</p>
                                <div class="text-gray-900 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $totalApplications; ?></div>
                        <div class="text-sm text-gray-500">Total Applications</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $approvedApplications; ?></div>
                        <div class="text-sm text-gray-500">Approved</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $pendingApplications; ?></div>
                        <div class="text-sm text-gray-500">Pending</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Applications</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidate Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($applications) > 0): ?>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($application['candidate_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($application['candidate_email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status = $application['status'] ?? 'pending';
                                        $statusClass = match($status) {
                                            'approved' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'reviewed' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($application['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <!-- View Button -->
                                            <a href="/apex-nexus-portal/admin/candidate-detail.php?id=<?php echo $application['candidate_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900 text-sm">
                                                View Candidate
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No applications found</p>
                                        <p class="text-sm mt-1">No candidates have applied to this job yet</p>
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