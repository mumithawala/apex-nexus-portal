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

// require_once '../includes/navbar.php';
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

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow mb-6">
        <form method="GET" class="flex flex-col  lg:flex-row gap-4">
            <!-- Search Input - Smaller -->
            <div class="flex-1 lg:w-70">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" placeholder="Search jobs..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="flex items-center gap-2 lg:w-70">
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="pending" <?php if ($status == 'pending')
                        echo 'selected'; ?>>Pending</option>
                    <option value="active" <?php if ($status == 'active')
                        echo 'selected'; ?>>Active</option>
                    <option value="closed" <?php if ($status == 'closed')
                        echo 'selected'; ?>>Closed</option>
                </select>

                 <!-- Filter Button -->
                <button type="submit"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm font-medium">
                    Filter
                </button>

                <!-- Reset Button -->
                <a href="jobs.php"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                    Reset
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="">
               

                <!-- Add Job Button - Larger and More Prominent -->
                <a href="/apex-recruit/admin/add-job.php"
                    class="px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg shadow hover:scale-105 transition font-semibold text-base flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H8m8 8l-4-4m0 0l4 4"></path>
                    </svg>
                    Add New Job
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="p-3 text-left">Job</th>
                    <th class="p-3 text-left">Company</th>
                    <th class="p-3 text-left">Location</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr class="border-t hover:bg-gray-50">

                        <td class="p-3">
                            <div class="font-semibold"><?php echo $job['title']; ?></div>
                            <div class="text-xs text-gray-400"><?php echo $job['created_at']; ?></div>
                        </td>

                        <td class="p-3"><?php echo $job['company_name']; ?></td>
                        <td class="p-3"><?php echo $job['location']; ?></td>

                        <td class="p-3">
                            <?php
                            $statusClass = [
                                'active' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'closed' => 'bg-red-100 text-red-700'
                            ];
                            ?>
                            <span class="px-3 py-1 text-xs rounded-full <?php echo $statusClass[$job['status']]; ?>">
                                <?php echo ucfirst($job['status']); ?>
                            </span>
                        </td>

                        <td class="p-3 flex gap-2">

                            <?php if ($job['status'] == 'pending'): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">✔</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">✖</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <button class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">🗑</button>
                            </form>

                        </td>

                    </tr>
                <?php endforeach; ?>

                <?php if (empty($jobs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-10 text-gray-500">
                            No jobs found
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

</div>

<?php require_once '../includes/footer.php'; ?>