<?php
/**
 * Admin Jobs Management
 */

$pageTitle = "Manage Jobs - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $jobId = (int)($_POST['job_id'] ?? 0);
    
    if ($jobId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            switch ($action) {
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE jobs SET status = 'active', updated_at = NOW() WHERE id = ? AND is_deleted = 0");
                    $stmt->execute([$jobId]);
                    setFlash('success', 'Job approved successfully');
                    break;
                    
                case 'reject':
                    $stmt = $pdo->prepare("UPDATE jobs SET status = 'closed', updated_at = NOW() WHERE id = ? AND is_deleted = 0");
                    $stmt->execute([$jobId]);
                    setFlash('success', 'Job rejected successfully');
                    break;
                    
                case 'delete':
                    if (softDelete($pdo, 'jobs', $jobId)) {
                        setFlash('success', 'Job deleted successfully');
                    } else {
                        setFlash('error', 'Failed to delete job');
                    }
                    break;
            }
        } catch (PDOException $e) {
            error_log("Job action error: " . $e->getMessage());
            setFlash('error', 'Action failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/jobs.php');
}

// Fetch jobs with filtering
$search = clean($_GET['search'] ?? '');
$status = clean($_GET['status'] ?? '');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Build query
    $whereConditions = ["j.is_deleted = 0"];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "j.title LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $whereConditions[] = "j.status = ?";
        $params[] = $status;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM jobs j LEFT JOIN companies c ON j.company_id = c.id $whereClause";
    $totalJobs = $pdo->prepare($countQuery);
    $totalJobs->execute($params);
    $totalCount = $totalJobs->fetchColumn();
    
    // Pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Fetch jobs
    $jobsQuery = "
        SELECT j.*, c.name as company_name 
        FROM jobs j 
        LEFT JOIN companies c ON j.company_id = c.id 
        $whereClause 
        ORDER BY j.created_at DESC 
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($jobsQuery);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    // Calculate pagination
    $totalPages = ceil($totalCount / $perPage);
    
} catch (PDOException $e) {
    error_log("Jobs fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load jobs data');
    $jobs = [];
    $totalCount = 0;
    $totalPages = 0;
}

// Include navbar
require_once '../includes/navbar.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Jobs</h1>
                <p class="text-gray-600 mt-2">Review and manage all job postings</p>
            </div>
            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                <?php echo number_format($totalCount); ?> Total
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by job title..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full"
                    >
                </div>
            </div>
            
            <div class="md:w-48">
                <select 
                    name="status" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Filter
            </button>
            
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="/apex-nexus-portal/admin/jobs.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Posted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($job['company_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($job['category'] ?? 'N/A'); ?>
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
                                    <?php echo formatDate($job['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($job['status'] === 'pending'): ?>
                                            <!-- Approve Button -->
                                            <form method="POST" onsubmit="return confirm('Approve this job posting?')">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900 text-sm">
                                                    Approve
                                                </button>
                                            </form>
                                            
                                            <!-- Reject Button -->
                                            <form method="POST" onsubmit="return confirm('Reject this job posting?')">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                                    Reject
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" onsubmit="return confirm('Delete this job posting? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" class="text-gray-600 hover:text-gray-900 text-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No jobs found</p>
                                    <p class="text-sm mt-1">
                                        <?php if (!empty($search) || !empty($status)): ?>
                                            Try adjusting your search or filter criteria
                                        <?php else: ?>
                                            No job postings have been created yet
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                                <span class="font-medium"><?php echo min($offset + $perPage, $totalCount); ?></span> of 
                                <span class="font-medium"><?php echo $totalCount; ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>