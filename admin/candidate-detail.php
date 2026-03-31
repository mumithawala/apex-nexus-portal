<?php
/**
 * Admin Candidate Detail Page
 */

$pageTitle = "Candidate Detail - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Get and validate candidate ID
$candidateId = (int)($_GET['id'] ?? 0);
if ($candidateId <= 0) {
    setFlash('error', 'Invalid candidate ID');
    redirect('/apex-nexus-portal/admin/candidates.php');
}

// Fetch candidate details and applications
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Fetch candidate details with user info
    $candidateStmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name, u.email as user_email, u.created_at as joined_date
        FROM candidates c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.id = ? AND c.is_deleted = 0 AND u.is_deleted = 0
    ");
    $candidateStmt->execute([$candidateId]);
    $candidate = $candidateStmt->fetch();
    
    if (!$candidate) {
        setFlash('error', 'Candidate not found');
        redirect('/apex-nexus-portal/admin/candidates.php');
    }
    
    // Fetch candidate applications
    $applicationsStmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.location, c.company_name as company_name
        FROM applications a 
        LEFT JOIN jobs j ON a.job_id = j.id 
        LEFT JOIN companies c ON j.company_id = c.id 
        WHERE a.candidate_id = ? AND a.is_deleted = 0 
        ORDER BY a.created_at DESC
    ");
    $applicationsStmt->execute([$candidateId]);
    $applications = $applicationsStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Candidate detail error: " . $e->getMessage());
    setFlash('error', 'Failed to load candidate details');
    redirect('/apex-nexus-portal/admin/candidates.php');
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
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Candidate Details</h1>
            <p class="text-sm text-gray-500">View candidate information and applications</p>
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
            <a href="/apex-nexus-portal/admin/candidates.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Candidates
            </a>
        </div>

        <!-- Candidate Profile Card -->
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center md:space-x-6">
                    <!-- Candidate Avatar -->
                    <div class="flex-shrink-0 mb-4 md:mb-0">
                        <div class="h-24 w-24 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold text-green-600">
                                <?php echo strtoupper(substr(($candidate['first_name'] ?? ''), 0, 1) . substr(($candidate['last_name'] ?? ''), 0, 1)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Candidate Info -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            <?php echo htmlspecialchars(($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? '')); ?>
                        </h1>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($candidate['user_email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($candidate['phone'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Location</p>
                                <p class="text-gray-900">
                                    <?php 
                                    $location = [];
                                    if (!empty($candidate['city'])) $location[] = $candidate['city'];
                                    if (!empty($candidate['state'])) $location[] = $candidate['state'];
                                    if (!empty($candidate['country'])) $location[] = $candidate['country'];
                                    echo !empty($location) ? htmlspecialchars(implode(', ', $location)) : 'N/A';
                                    ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date of Birth</p>
                                <p class="text-gray-900"><?php echo !empty($candidate['date_of_birth']) ? formatDate($candidate['date_of_birth']) : 'N/A'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Gender</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($candidate['gender'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Joined Date</p>
                                <p class="text-gray-900"><?php echo formatDate($candidate['joined_date']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Resume Download -->
                        <?php if (!empty($candidate['resume']) && $candidate['resume'] !== 'N/A'): ?>
                            <div class="mb-4">
                                <a href="/apex-nexus-portal/assets/uploads/resumes/<?php echo htmlspecialchars($candidate['resume']); ?>" 
                                   target="_blank" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download Resume
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Professional Information -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-gray-200">
                    <!-- Current Job -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Current Position</h3>
                        <p class="text-gray-600 text-sm">
                            <div class="font-medium"><?php echo htmlspecialchars($candidate['current_job_title'] ?? 'N/A'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($candidate['current_company'] ?? ''); ?></div>
                        </p>
                    </div>
                    
                    <!-- Experience -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Total Experience</h3>
                        <p class="text-gray-600 text-sm">
                            <?php 
                            $experience = $candidate['total_experience'] ?? '';
                            if (!empty($experience) && $experience !== 'N/A') {
                                echo htmlspecialchars($experience) . ' years';
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <!-- Job Preferences -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Job Preferences</h3>
                        <p class="text-gray-600 text-sm">
                            <div><strong>Type:</strong> <?php echo htmlspecialchars($candidate['job_type'] ?? 'N/A'); ?></div>
                            <div><strong>Location:</strong> <?php echo htmlspecialchars($candidate['preferred_location'] ?? 'N/A'); ?></div>
                            <div><strong>Notice:</strong> <?php echo htmlspecialchars($candidate['notice_period'] ?? 'N/A'); ?></div>
                        </p>
                    </div>
                </div>
                
                <!-- Salary Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Current Salary</h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($candidate['current_salary'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Expected Salary</h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($candidate['expected_salary'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <!-- Professional Links -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">LinkedIn</h3>
                        <?php if (!empty($candidate['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($candidate['linkedin_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                <?php echo htmlspecialchars($candidate['linkedin_url']); ?>
                            </a>
                        <?php else: ?>
                            <p class="text-gray-600 text-sm">Not provided</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Portfolio</h3>
                        <?php if (!empty($candidate['portfolio_url'])): ?>
                            <a href="<?php echo htmlspecialchars($candidate['portfolio_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                <?php echo htmlspecialchars($candidate['portfolio_url']); ?>
                            </a>
                        <?php else: ?>
                            <p class="text-gray-600 text-sm">Not provided</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Skills, Experience, Education -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-gray-200">
                    <!-- Skills -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Skills</h3>
                        <p class="text-gray-600 text-sm">
                            <?php 
                            $skills = $candidate['skills'] ?? '';
                            if (!empty($skills) && $skills !== 'N/A') {
                                echo nl2br(htmlspecialchars($skills));
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <!-- Work Experience -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Work Experience</h3>
                        <p class="text-gray-600 text-sm">
                            <?php 
                            $experience = $candidate['experience'] ?? '';
                            if (!empty($experience) && $experience !== 'N/A') {
                                echo nl2br(htmlspecialchars($experience));
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <!-- Education -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Education</h3>
                        <p class="text-gray-600 text-sm">
                            <?php 
                            $education = $candidate['education'] ?? '';
                            if (!empty($education) && $education !== 'N/A') {
                                echo nl2br(htmlspecialchars($education));
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-gray-200 mt-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($applications); ?></div>
                        <div class="text-sm text-gray-500">Total Applications</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            <?php echo count(array_filter($applications, fn($a) => $a['status'] === 'approved')); ?>
                        </div>
                        <div class="text-sm text-gray-500">Approved</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">
                            <?php echo count(array_filter($applications, fn($a) => $a['status'] === 'pending')); ?>
                        </div>
                        <div class="text-sm text-gray-500">Pending</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Application History</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($applications) > 0): ?>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($application['job_title']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($application['company_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($application['location'] ?? 'N/A'); ?>
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
                                        <p class="text-sm mt-1">This candidate hasn't applied to any jobs yet</p>
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
