<?php
require_once '../includes/auth.php';
requireRole('candidate');

$pageTitle = "My Applications - Apex Nexus";
require_once '../includes/header.php';
// require_once '../includes/navbar.php';

// Get current candidate record
$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM candidates WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);
$candidateId = $candidate['id'] ?? null;

// Handle withdraw action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_application'])) {
    $applicationId = $_POST['application_id'] ?? '';
    
    if (!empty($applicationId) && $candidateId) {
        // Verify application belongs to candidate
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ? AND candidate_id = ? AND is_deleted = 0");
        $stmt->execute([$applicationId, $candidateId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application) {
            // Mark as deleted (soft delete)
            $stmt = $pdo->prepare("UPDATE applications SET is_deleted = 1 WHERE id = ?");
            if ($stmt->execute([$applicationId])) {
                setFlash('success', 'Application withdrawn successfully');
            } else {
                setFlash('error', 'Failed to withdraw application');
            }
        }
    }
    
    redirect('candidate/my-applications.php');
}

// Get filter status
$statusFilter = $_GET['status'] ?? 'all';

// Build WHERE clause
$where = ["a.candidate_id = ?", "a.is_deleted = 0"];
$params = [$candidateId];

if ($statusFilter !== 'all') {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Get applications with job and company details
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.employment_type, j.work_mode, j.salary_visible, j.salary,
           c.company_name, c.city as company_city, c.state as company_state
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    $whereClause
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for summary
$statusCounts = ['all' => 0, 'applied' => 0, 'reviewed' => 0, 'shortlisted' => 0, 'rejected' => 0];
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM applications 
    WHERE candidate_id = ? AND is_deleted = 0 
    GROUP BY status
");
$stmt->execute([$candidateId]);
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($statusData as $row) {
    $statusCounts[$row['status']] = $row['count'];
}
$statusCounts['all'] = array_sum($statusCounts);
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
        <!-- Page Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent mb-2">
                        My Applications
                    </h1>
                    <p class="text-slate-600 text-lg">Track and manage your job applications</p>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl font-semibold shadow-lg">
                    <?php echo $statusCounts['all']; ?> Total
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-slate-900"><?php echo $statusCounts['all']; ?></span>
                </div>
                <div class="text-sm font-medium text-slate-600">Total Applications</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-blue-600"><?php echo $statusCounts['applied']; ?></span>
                </div>
                <div class="text-sm font-medium text-slate-600">Applied</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-amber-600"><?php echo $statusCounts['reviewed']; ?></span>
                </div>
                <div class="text-sm font-medium text-slate-600">Under Review</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-green-600"><?php echo $statusCounts['shortlisted']; ?></span>
                </div>
                <div class="text-sm font-medium text-slate-600">Shortlisted</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-red-600"><?php echo $statusCounts['rejected']; ?></span>
                </div>
                <div class="text-sm font-medium text-slate-600">Rejected</div>
            </div>
        </div>

        <!-- Status Filter Tabs -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 mb-8">
            <div class="border-b border-slate-200">
                <nav class="flex overflow-x-auto" aria-label="Tabs">
                    <a href="?status=all" 
                       class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap
                              <?php echo $statusFilter === 'all' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        All (<?php echo $statusCounts['all']; ?>)
                    </a>
                    <a href="?status=applied" 
                       class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap
                              <?php echo $statusFilter === 'applied' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Applied (<?php echo $statusCounts['applied']; ?>)
                    </a>
                    <a href="?status=reviewed" 
                       class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap
                              <?php echo $statusFilter === 'reviewed' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Under Review (<?php echo $statusCounts['reviewed']; ?>)
                    </a>
                    <a href="?status=shortlisted" 
                       class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap
                              <?php echo $statusFilter === 'shortlisted' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Shortlisted (<?php echo $statusCounts['shortlisted']; ?>)
                    </a>
                    <a href="?status=rejected" 
                       class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap
                              <?php echo $statusFilter === 'rejected' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Rejected (<?php echo $statusCounts['rejected']; ?>)
                    </a>
                </nav>
            </div>
        </div>

        <!-- Applications Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
            <div class="p-8">
                <?php if (empty($applications)): ?>
                    <div class="text-center py-16">
                        <div class="w-24 h-24 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 mb-3">
                            <?php
                            switch ($statusFilter) {
                                case 'applied':
                                    echo 'No applied applications yet';
                                    break;
                                case 'reviewed':
                                    echo 'No applications under review';
                                    break;
                                case 'shortlisted':
                                    echo 'No shortlisted applications yet';
                                    break;
                                case 'rejected':
                                    echo 'No rejected applications';
                                    break;
                                default:
                                    echo 'No applications yet';
                            }
                            ?>
                        </h3>
                        <p class="text-slate-600 mb-6 text-lg">
                            <?php
                            if ($statusFilter === 'all') {
                                echo 'Start applying for jobs to see them here';
                            } else {
                                echo 'No applications match this filter';
                            }
                            ?>
                        </p>
                        <?php if ($statusFilter === 'all'): ?>
                            <a href="/apex-nexus-portal/candidate/search-jobs.php" 
                               class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Search Jobs
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">#</th>
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Job Title</th>
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location</th>
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Applied Date</th>
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                    <th class="pb-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($applications as $index => $app): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 text-sm font-medium text-slate-600"><?php echo $index + 1; ?></td>
                                        <td class="py-4">
                                            <div class="font-semibold text-slate-900 text-sm mb-1"><?php echo htmlspecialchars($app['job_title']); ?></div>
                                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A8.937 8.937 0 0112 21a8.937 8.937 0 01-9-8.745M21 12a9 9 0 01-9 9m0-9a9 9 0 00-9 9m9 9v-6m0 0V3m0 6h-6m6 0h6"/>
                                                    </svg>
                                                    <?php echo htmlspecialchars($app['employment_type']); ?>
                                                </span>
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                                    </svg>
                                                    <?php echo htmlspecialchars($app['work_mode']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 text-sm font-medium text-slate-700"><?php echo htmlspecialchars($app['company_name']); ?></td>
                                        <td class="py-4 text-sm text-slate-600"><?php echo htmlspecialchars($app['company_city'] . ', ' . $app['company_state']); ?></td>
                                        <td class="py-4 text-sm text-slate-600"><?php echo timeAgo($app['created_at']); ?></td>
                                        <td class="py-4">
                                            <?php
                                            $statusColors = [
                                                'applied' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                'reviewed' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'shortlisted' => 'bg-green-100 text-green-800 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-800 border-red-200'
                                            ];
                                            $statusClass = $statusColors[$app['status']] ?? 'bg-slate-100 text-slate-800 border-slate-200';
                                            ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4">
                                            <div class="flex items-center gap-3">
                                                <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $app['job_id']; ?>" 
                                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 text-sm font-semibold transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    View Job
                                                </a>
                                                <?php if ($app['status'] === 'applied'): ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to withdraw this application?');" 
                                                          class="inline">
                                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                        <input type="hidden" name="withdraw_application" value="1">
                                                        <button type="submit" 
                                                                class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 text-sm font-semibold transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Withdraw
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>