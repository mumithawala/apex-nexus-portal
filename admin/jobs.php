<?php
$pageTitle = "Manage Jobs - Admin";
require_once '../includes/header.php';
requireRole('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $jobId = (int) ($_POST['job_id'] ?? 0);

    if ($jobId > 0) {
        $database = new Database();
        $pdo = $database->getConnection();

        switch ($action) {
            case 'approve':
                $pdo->prepare("UPDATE jobs SET status='active' WHERE id=?")->execute([$jobId]);
                break;
            case 'reject':
                $pdo->prepare("UPDATE jobs SET status='closed' WHERE id=?")->execute([$jobId]);
                break;
            case 'delete':
                $pdo->prepare("UPDATE jobs SET is_deleted=1 WHERE id=?")->execute([$jobId]);
                break;
        }
    }
    header("Location: jobs.php");
    exit;
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$database = new Database();
$pdo = $database->getConnection();

$where = "WHERE j.is_deleted=0";
$params = [];

if ($search) {
    $where .= " AND j.title LIKE ?";
    $params[] = "%$search%";
}

if ($status) {
    $where .= " AND j.status = ?";
    $params[] = $status;
}

// Fetch jobs
$stmt = $pdo->prepare("
    SELECT j.*, c.company_name as company_name 
    FROM jobs j 
    LEFT JOIN companies c ON j.company_id = c.id
    $where
    ORDER BY j.created_at DESC
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();
$totalCount = count($jobs);

// require_once '../includes/auth.php';

require_once '../includes/urls.php';
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div
        class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Manage Jobs</h1>
            <p class="text-sm text-gray-500">Manage all job postings</p>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-4">
            <!-- Notification -->
            <button class="relative p-2 rounded-full hover:bg-gray-100 transition">
                🔔
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- Profile -->
            <div
                class="flex items-center gap-3 bg-gray-100 px-3 py-2 rounded-full cursor-pointer hover:bg-gray-200 transition">
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

    <!-- Modern Search and Filter Section -->
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6">
                <form method="GET" class="space-y-4">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" name="search" placeholder="Search jobs by title, company, or location..."
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="lg:w-48">
                            <select name="status"
                                class="w-full px-3 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="pending" <?php if ($status == 'pending') echo 'selected'; ?>>Pending</option>
                                <option value="active" <?php if ($status == 'active') echo 'selected'; ?>>Active</option>
                                <option value="closed" <?php if ($status == 'closed') echo 'selected'; ?>>Closed</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button type="submit"
                                class="px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm font-medium">
                                Filter
                            </button>
                            <a href="jobs.php"
                                class="px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                                Reset
                            </a>
                            <a href="<?php echo $ADMIN_URL; ?>/add-job.php"
                                class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Job
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Jobs Grid -->
    <div class="px-4 sm:px-6 lg:px-8 pb-8">
        <?php if (!empty($jobs)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($jobs as $job): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                        <!-- Job Header -->
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($job['title']); ?>
                                    </h3>
                                    <div class="flex items-center text-sm text-gray-600 mb-2">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($job['location'] ?? 'Not specified'); ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="ml-4">
                                    <?php
                                    $statusClass = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'closed' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass[$job['status']]; ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Job Description Preview -->
                            <?php if (!empty($job['description'])): ?>
                                <div class="mb-4">
                                    <p class="text-sm font-semibold text-gray-600 line-clamp-3">
                                        <?php echo htmlspecialchars(substr(strip_tags($job['description']), 0, 150)) . '...'; ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Additional Info -->
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                                <span>Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                                <?php if (!empty($job['deadline'])): ?>
                                    <span>Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2 pt-4 border-t border-gray-100">
                                <a href="<?php echo $ADMIN_URL; ?>/add-job.php?id=<?php echo $job['id']; ?>"
                                   class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition text-sm font-medium text-center">
                                    Edit
                                </a>

                                <?php if ($job['status'] == 'pending'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" class="w-full px-3 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition text-sm font-medium">
                                            Approve
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" class="w-full px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition text-sm font-medium"
                                            onclick="return confirm('Are you sure you want to delete this job?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Results Summary -->
            <div class="mt-8 text-center text-sm text-gray-600">
                Showing <?php echo count($jobs); ?> of <?php echo $totalCount; ?> jobs
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No jobs found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($search || $status): ?>
                        No jobs match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        Get started by adding your first job posting.
                    <?php endif; ?>
                </p>
                <a href="<?php echo $ADMIN_URL; ?>/add-job.php"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    Add New Job
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

</div>

<?php require_once '../includes/footer.php'; ?>