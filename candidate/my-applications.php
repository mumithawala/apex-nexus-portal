<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
requireRole('candidate');
$pageTitle = "My Applications - Apex Nexus";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

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
    
    redirect('/apex-nexus-portal/candidate/my-applications.php');
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
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
    
    <!-- Page Header -->
    <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">My Applications</h1>
                <p class="text-gray-600 mt-1">Track and manage your job applications</p>
            </div>
            <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full font-medium">
                <?php echo $statusCounts['all']; ?> Total
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
            <div class="text-2xl font-bold text-gray-800"><?php echo $statusCounts['all']; ?></div>
            <div class="text-sm text-gray-500">Total</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-blue-600"><?php echo $statusCounts['applied']; ?></div>
            <div class="text-sm text-gray-500">Applied</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-amber-600"><?php echo $statusCounts['reviewed']; ?></div>
            <div class="text-sm text-gray-500">Reviewed</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-green-600"><?php echo $statusCounts['shortlisted']; ?></div>
            <div class="text-sm text-gray-500">Shortlisted</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-red-600"><?php echo $statusCounts['rejected']; ?></div>
            <div class="text-sm text-gray-500">Rejected</div>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="bg-white rounded-2xl border border-gray-100 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px" aria-label="Tabs">
                <a href="?status=all" 
                   class="tab-button <?php echo $statusFilter === 'all' ? 'active' : ''; ?> py-4 px-6 border-b-2 font-medium text-sm">
                    All (<?php echo $statusCounts['all']; ?>)
                </a>
                <a href="?status=applied" 
                   class="tab-button <?php echo $statusFilter === 'applied' ? 'active' : ''; ?> py-4 px-6 border-b-2 font-medium text-sm">
                    Applied (<?php echo $statusCounts['applied']; ?>)
                </a>
                <a href="?status=reviewed" 
                   class="tab-button <?php echo $statusFilter === 'reviewed' ? 'active' : ''; ?> py-4 px-6 border-b-2 font-medium text-sm">
                    Under Review (<?php echo $statusCounts['reviewed']; ?>)
                </a>
                <a href="?status=shortlisted" 
                   class="tab-button <?php echo $statusFilter === 'shortlisted' ? 'active' : ''; ?> py-4 px-6 border-b-2 font-medium text-sm">
                    Shortlisted (<?php echo $statusCounts['shortlisted']; ?>)
                </a>
                <a href="?status=rejected" 
                   class="tab-button <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?> py-4 px-6 border-b-2 font-medium text-sm">
                    Rejected (<?php echo $statusCounts['rejected']; ?>)
                </a>
            </nav>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-2xl border border-gray-100">
        <div class="p-6">
            <?php if (empty($applications)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm12 2H4v8h12V6z" clip-rule="evenodd"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">
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
                    <p class="text-gray-500 mb-4">
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
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Search Jobs
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b">
                                <th class="pb-3">#</th>
                                <th class="pb-3">Job Title</th>
                                <th class="pb-3">Company</th>
                                <th class="pb-3">Location</th>
                                <th class="pb-3">Applied Date</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $index => $app): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4 text-sm text-gray-600"><?php echo $index + 1; ?></td>
                                    <td class="py-4">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($app['job_title']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($app['employment_type']); ?> · <?php echo htmlspecialchars($app['work_mode']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 text-gray-600"><?php echo htmlspecialchars($app['company_name']); ?></td>
                                    <td class="py-4 text-gray-600"><?php echo htmlspecialchars($app['company_city'] . ', ' . $app['company_state']); ?></td>
                                    <td class="py-4 text-gray-600"><?php echo timeAgo($app['created_at']); ?></td>
                                    <td class="py-4">
                                        <span class="badge badge-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <div class="flex gap-2">
                                            <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $app['job_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                                View Job
                                            </a>
                                            <?php if ($app['status'] === 'applied'): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to withdraw this application?');" 
                                                      class="inline">
                                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                    <input type="hidden" name="withdraw_application" value="1">
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-700 text-sm font-medium">
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

  </main>
</div>

<?php require_once '../includes/footer.php'; ?>