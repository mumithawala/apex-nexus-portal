<?php
/**
 * Admin Categories Management
 */

$pageTitle = "Manage Categories - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    
    if ($action === 'delete' && $categoryId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if category is being used by any jobs
            $stmt = $pdo->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $jobCount = $stmt->fetch()['job_count'];
            
            if ($jobCount > 0) {
                setFlash('error', 'Cannot delete category - it is being used by ' . $jobCount . ' job(s)');
            } else {
                // Delete category directly (hard delete since no is_deleted column)
                $stmt = $pdo->prepare("DELETE FROM job_categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                setFlash('success', 'Category deleted successfully');
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Category delete error: " . $e->getMessage());
            setFlash('error', 'Delete failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/categories.php');
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    
    if ($categoryId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Get current status
            $stmt = $pdo->prepare("SELECT is_active FROM job_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch();
            
            if ($category) {
                $newStatus = $category['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE job_categories SET is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $categoryId]);
                setFlash('success', 'Category status updated successfully');
            } else {
                setFlash('error', 'Category not found');
            }
        } catch (PDOException $e) {
            error_log("Category status toggle error: " . $e->getMessage());
            setFlash('error', 'Status update failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/categories.php');
}

// Fetch categories with search
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
    
    // Fetch categories with job counts - simplified query
    $categoriesQuery = "
        SELECT *, 
               (SELECT COUNT(*) FROM jobs WHERE category_id = job_categories.id AND is_deleted = 0) as total_jobs,
               (SELECT COUNT(*) FROM jobs WHERE category_id = job_categories.id AND is_deleted = 0 AND status = 'active') as active_jobs
        FROM job_categories 
        $whereClause 
        ORDER BY created_at DESC
    ";
    
    // Debug: Log the query
    error_log("Categories Query: " . $categoriesQuery);
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($categoriesQuery);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Debug: Log results
    error_log("Categories found: " . count($categories));
    if (count($categories) > 0) {
        error_log("First category: " . print_r($categories[0], true));
    }
    
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load categories data: ' . $e->getMessage());
    $categories = [];
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
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Manage Categories</h1>
            <p class="text-sm text-gray-500">Manage all job categories in the system</p>
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
                            placeholder="Search categories..." 
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
                    <a href="/apex-nexus-portal/admin/categories.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Clear
                    </a>
                <?php endif; ?>

                <a href="/apex-nexus-portal/admin/add-category.php" class="gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Add Category
                </a>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-lg shadow">
            <!-- Debug Info -->
         
            
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo number_format($category['total_jobs']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?php echo number_format($category['active_jobs']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($category['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <!-- Edit Button -->
                                            <a href="/apex-nexus-portal/admin/add-category.php?id=<?php echo $category['id']; ?>" 
                                               class="text-green-600 hover:text-green-900 text-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            
                                            <!-- Status Toggle Button -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" 
                                                        class="<?php echo $category['is_active'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-blue-600 hover:text-blue-900'; ?> text-sm"
                                                        title="<?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <?php if ($category['is_active']): ?>
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
                                            <form method="POST" onsubmit="return confirm('Delete this category? This action cannot be undone.')" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm" 
                                                        <?php echo $category['total_jobs'] > 0 ? 'disabled title="Cannot delete - category has jobs"' : ''; ?>>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No categories found</p>
                                        <p class="text-sm mt-1">
                                            <?php if (!empty($search)): ?>
                                                Try adjusting your search criteria
                                            <?php else: ?>
                                                No categories have been created yet
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
