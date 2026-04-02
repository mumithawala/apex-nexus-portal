<?php
/**
 * Admin Departments Management
 */

$pageTitle = "Manage Departments - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $departmentId = (int)($_POST['department_id'] ?? 0);
    
    if ($action === 'delete' && $departmentId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if department is being used by any jobs
            $stmt = $pdo->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE department_id = ?");
            $stmt->execute([$departmentId]);
            $jobCount = $stmt->fetch()['job_count'];
            
            if ($jobCount > 0) {
                setFlash('error', 'Cannot delete department - it is being used by ' . $jobCount . ' job(s)');
            } else {
                // Delete department directly (hard delete since no is_deleted column)
                $stmt = $pdo->prepare("DELETE FROM job_departments WHERE id = ?");
                $stmt->execute([$departmentId]);
                setFlash('success', 'Department deleted successfully');
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Department delete error: " . $e->getMessage());
            setFlash('error', 'Delete failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/departments.php');
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $departmentId = (int)($_POST['department_id'] ?? 0);
    
    if ($departmentId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Get current status
            $stmt = $pdo->prepare("SELECT is_active FROM job_departments WHERE id = ?");
            $stmt->execute([$departmentId]);
            $department = $stmt->fetch();
            
            if ($department) {
                $newStatus = $department['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE job_departments SET is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $departmentId]);
                setFlash('success', 'Department status updated successfully');
            } else {
                setFlash('error', 'Department not found');
            }
        } catch (PDOException $e) {
            error_log("Department status toggle error: " . $e->getMessage());
            setFlash('error', 'Status update failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/departments.php');
}

// Fetch departments with search
$search = clean($_GET['search'] ?? '');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Build query - start simple without is_deleted filter
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "name LIKE ?";
        $params[] = "%$search%";
    }
    
    $whereClause = count($whereConditions) > 0 ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Fetch departments with job counts - simplified query
    $departmentsQuery = "
        SELECT *, 
               (SELECT COUNT(*) FROM jobs WHERE department_id = job_departments.id AND is_deleted = 0) as total_jobs,
               (SELECT COUNT(*) FROM jobs WHERE department_id = job_departments.id AND is_deleted = 0 AND status = 'active') as active_jobs
        FROM job_departments 
        $whereClause 
        ORDER BY created_at DESC
    ";
    
    // Debug: Log the query
    error_log("Departments Query: " . $departmentsQuery);
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($departmentsQuery);
    $stmt->execute($params);
    $departments = $stmt->fetchAll();
    
    // Debug: Log results
    error_log("Departments found: " . count($departments));
    if (count($departments) > 0) {
        error_log("First department: " . print_r($departments[0], true));
    }
    
} catch (PDOException $e) {
    error_log("Departments fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load departments data: ' . $e->getMessage());
    $departments = [];
}

// Include navbar and sidebar
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Manage Departments</h1>
            <p class="text-sm text-gray-500">Manage all departments in the system</p>
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
        <!-- Search -->
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
                            placeholder="Search departments..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full"
                        >
                    </div>
                </div>
                
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                   </svg>
                </button>
                
                <?php if (!empty($search)): ?>
                    <a href="/apex-nexus-portal/admin/departments.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Clear
                    </a>
                <?php endif; ?>

                <a href="/apex-nexus-portal/admin/add-department.php" class="gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Add Department
                </a>
            </form>
        </div>

        <!-- Departments Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($departments) > 0): ?>
                            <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($department['name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $department['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $department['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo number_format($department['total_jobs']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?php echo number_format($department['active_jobs']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($department['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <!-- Edit Button -->
                                            <a href="/apex-nexus-portal/admin/add-department.php?id=<?php echo $department['id']; ?>" 
                                               class="text-green-600 hover:text-green-900 text-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            
                                            <!-- Status Toggle Button -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="department_id" value="<?php echo $department['id']; ?>">
                                                <button type="submit" 
                                                        class="<?php echo $department['is_active'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-blue-600 hover:text-blue-900'; ?> text-sm"
                                                        title="<?php echo $department['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <?php if ($department['is_active']): ?>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Delete Button -->
                                            <form method="POST" onsubmit="return confirm('Delete this department? This action cannot be undone.')" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="department_id" value="<?php echo $department['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm" 
                                                        <?php echo $department['total_jobs'] > 0 ? 'disabled title="Cannot delete - department has jobs"' : ''; ?>>
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No departments found</p>
                                        <p class="text-sm mt-1">
                                            <?php if (!empty($search)): ?>
                                                Try adjusting your search criteria
                                            <?php else: ?>
                                                No departments have been created yet
                                            <?php endif; ?>
                                        </p>
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
