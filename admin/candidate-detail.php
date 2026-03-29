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
        SELECT c.*, u.name as user_name, u.email as user_email, u.created_at as joined_date
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
        SELECT a.*, j.title as job_title, j.location, c.name as company_name, c.name as company_name
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

// Include navbar
require_once '../includes/navbar.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
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
                            <?php echo strtoupper(substr($candidate['user_name'], 0, 2)); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Candidate Info -->
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($candidate['user_name']); ?>
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
                            <p class="text-gray-900"><?php echo htmlspecialchars($candidate['location'] ?? 'N/A'); ?></p>
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
            
            <!-- Additional Details -->
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
                
                <!-- Experience -->
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Experience</h3>
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

<?php require_once '../includes/footer.php'; ?>