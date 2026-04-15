<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Manage Jobs - Apex Nexus";
require_once '../includes/header.php';
?>

<!-- Company CSS Imports -->
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-modern.css">

<?php
$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get company record
$stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company['id'] ?? null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $jobId = (int) ($_POST['job_id'] ?? 0);
    
    if ($jobId > 0) {
        try {
            // Verify job belongs to this company
            $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND company_id = ? AND is_deleted = 0");
            $stmt->execute([$jobId, $companyId]);
            if ($stmt->fetch()) {
                switch ($action) {
                    case 'close':
                        $stmt = $pdo->prepare("UPDATE jobs SET status = 'closed', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$jobId]);
                        setFlash('success', 'Job closed successfully');
                        break;
                    case 'delete':
                        $stmt = $pdo->prepare("UPDATE jobs SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$jobId]);
                        setFlash('success', 'Job deleted successfully');
                        break;
                }
            }
        } catch (PDOException $e) {
            error_log("Job action error: " . $e->getMessage());
            setFlash('error', 'Failed to update job');
        }
    }
    redirect('/apex-nexus-portal/company/manage-jobs.php');
}

// Get filter status
$statusFilter = clean($_GET['status'] ?? '');

// Fetch jobs with application counts
try {
    $whereConditions = ["j.company_id = ?", "j.is_deleted = 0"];
    $params = [$companyId];
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "j.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    $stmt = $pdo->prepare("
        SELECT j.*, COUNT(a.id) as application_count
        FROM jobs j 
        LEFT JOIN applications a ON j.id = a.job_id AND a.is_deleted = 0
        $whereClause
        GROUP BY j.id
        ORDER BY j.created_at DESC
    ");
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    // Get status counts for tabs
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count FROM jobs 
        WHERE company_id = ? AND is_deleted = 0
        GROUP BY status
    ");
    $stmt->execute([$companyId]);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    error_log("Jobs fetch error: " . $e->getMessage());
    $jobs = [];
    $statusCounts = [];
}
?>

<!-- Modern Company Navigation -->
<?php include '../includes/company-navbar.php'; ?>

<div class="min-h-screen bg-gray-50 p-6 lg:p-8 mt-20">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-2">
                    My Job Postings
                </h1>
                <p class="text-gray-600">Manage your active job listings and track applications.</p>
            </div>
            <a href="/apex-nexus-portal/company/post-job.php" 
               class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl px-6 py-3 hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Post New Job
            </a>
        </div>

        <!-- Status Filter Tabs -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm mb-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <div class="border-b border-gray-200/50 backdrop-blur-sm relative z-10">
                <nav class="flex -mb-px" aria-label="Tabs">
                    <a href="/apex-nexus-portal/company/manage-jobs.php" 
                       class="py-4 px-6 border-b-2 font-medium text-sm <?php echo empty($statusFilter) ? 'border-blue-500 text-blue-600 bg-gradient-to-r from-blue-50 to-transparent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent'; ?> transition-all duration-300">
                        All Jobs <?php echo !empty($statusFilter) ? '' : '(' . array_sum($statusCounts) . ')'; ?>
                    </a>
                    <a href="/apex-nexus-portal/company/manage-jobs.php?status=active" 
                       class="py-4 px-6 border-b-2 font-medium text-sm <?php echo $statusFilter === 'active' ? 'border-blue-500 text-blue-600 bg-gradient-to-r from-blue-50 to-transparent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent'; ?> transition-all duration-300">
                        Active <?php echo $statusFilter === 'active' ? '(' . ($statusCounts['active'] ?? 0) . ')' : ''; ?>
                    </a>
                    <a href="/apex-nexus-portal/company/manage-jobs.php?status=pending" 
                       class="py-4 px-6 border-b-2 font-medium text-sm <?php echo $statusFilter === 'pending' ? 'border-blue-500 text-blue-600 bg-gradient-to-r from-blue-50 to-transparent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent'; ?> transition-all duration-300">
                        Pending <?php echo $statusFilter === 'pending' ? '(' . ($statusCounts['pending'] ?? 0) . ')' : ''; ?>
                    </a>
                    <a href="/apex-nexus-portal/company/manage-jobs.php?status=closed" 
                       class="py-4 px-6 border-b-2 font-medium text-sm <?php echo $statusFilter === 'closed' ? 'border-blue-500 text-blue-600 bg-gradient-to-r from-blue-50 to-transparent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent'; ?> transition-all duration-300">
                        Closed <?php echo $statusFilter === 'closed' ? '(' . ($statusCounts['closed'] ?? 0) . ')' : ''; ?>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Jobs Grid -->
        <?php if (count($jobs) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($jobs as $job): ?>
                    <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm relative overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-br from-purple-400/10 to-pink-400/10 rounded-full blur-2xl"></div>
                        
                        <div class="p-6 relative z-10">
                            <!-- Status Badge -->
                            <div class="mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    <?php 
                                    echo match($job['status']) {
                                        'active' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                        default => 'bg-blue-100 text-blue-800'
                                    };
                                    ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </div>
                            
                            <!-- Job Title -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </h3>
                            
                            <!-- Location -->
                            <div class="flex items-center gap-2 text-gray-600 mb-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-sm"><?php echo htmlspecialchars($job['city'] ?? ''); ?></span>
                            </div>
                            
                            <!-- Work Mode & Type -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs font-medium">
                                    <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $job['work_mode'] ?? 'On-site'))); ?>
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium">
                                    <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $job['employment_type'] ?? ''))); ?>
                                </span>
                            </div>
                            
                            <!-- Applications Count -->
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $job['application_count']; ?></p>
                                    <p class="text-xs text-gray-500">Applications</p>
                                </div>
                            </div>
                            
                            <!-- Deadline -->
                            <?php if ($job['deadline']): ?>
                                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">Application Deadline</p>
                                    <p class="text-sm font-medium text-gray-900"><?php echo formatDate($job['deadline']); ?></p>
                                    <?php 
                                    $daysUntilDeadline = (new DateTime($job['deadline']))->diff(new DateTime())->days;
                                    if ($daysUntilDeadline <= 7 && $daysUntilDeadline >= 0): ?>
                                        <p class="text-xs text-red-600 mt-1">Expires in <?php echo $daysUntilDeadline; ?> days</p>
                                    <?php elseif ($daysUntilDeadline < 0): ?>
                                        <p class="text-xs text-gray-500 mt-1">Expired</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                                <a href="/apex-nexus-portal/company/applicants.php?job_id=<?php echo $job['id']; ?>" 
                                   class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </a>
                                <a href="/apex-nexus-portal/company/edit-job.php?id=<?php echo $job['id']; ?>" 
                                   class="p-2 text-gray-600 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <?php if ($job['status'] === 'active'): ?>
                                    <form method="POST" onsubmit="return confirm('Close this job posting?')">
                                        <input type="hidden" name="action" value="close">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" class="p-2 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition-colors" title="Close">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Delete this job posting permanently?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-pink-400/10 rounded-full blur-2xl"></div>
                
                <div class="p-12 text-center relative z-10">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full mx-auto mb-6 flex items-center justify-center">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No jobs posted yet</h3>
                    <p class="text-gray-600 mb-6">Get started by posting your first job opening to attract qualified candidates.</p>
                    <a href="/apex-nexus-portal/company/post-job.php" 
                       class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl px-6 py-3 hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Post Your First Job
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>