<?php
/**
 * Admin Companies Management
 */

$pageTitle = "Manage Companies - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $companyId = (int)($_POST['company_id'] ?? 0);
    
    if ($action === 'delete' && $companyId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Get user_id for this company
            $stmt = $pdo->prepare("SELECT user_id FROM companies WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$companyId]);
            $company = $stmt->fetch();
            
            if ($company && $company['user_id']) {
                // Soft delete company
                if (softDelete($pdo, 'companies', $companyId)) {
                    // Soft delete associated user
                    softDelete($pdo, 'users', $company['user_id']);
                    setFlash('success', 'Company deleted successfully');
                } else {
                    setFlash('error', 'Failed to delete company');
                }
            } else {
                setFlash('error', 'Company not found');
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Company delete error: " . $e->getMessage());
            setFlash('error', 'Delete failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/companies.php');
}

// Fetch companies with search
$search = clean($_GET['search'] ?? '');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Build query
    $whereConditions = ["c.is_deleted = 0", "u.is_deleted = 0"];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "c.name LIKE ?";
        $params[] = "%$search%";
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Fetch companies with user details
    $companiesQuery = "
        SELECT c.*, u.name as user_name, u.email as user_email, u.created_at as joined_date,
               (SELECT COUNT(*) FROM jobs WHERE company_id = c.id AND is_deleted = 0 AND status = 'active') as active_jobs_count
        FROM companies c 
        LEFT JOIN users u ON c.user_id = u.id 
        $whereClause 
        ORDER BY c.created_at DESC
    ";
    $stmt = $pdo->prepare($companiesQuery);
    $stmt->execute($params);
    $companies = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Companies fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load companies data');
    $companies = [];
}

// Include navbar
require_once '../includes/navbar.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manage Companies</h1>
        <p class="text-gray-600 mt-2">View and manage all registered companies</p>
    </div>

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
                        placeholder="Search by company name..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full"
                    >
                </div>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Search
            </button>
            
            <?php if (!empty($search)): ?>
                <a href="/apex-nexus-portal/admin/companies.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Companies Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Person</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Website</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jobs Posted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($companies) > 0): ?>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($company['user_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($company['user_email'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $website = $company['website'] ?? '';
                                    if (!empty($website) && $website !== 'N/A') {
                                        $websiteUrl = (strpos($website, 'http') === 0) ? $website : 'https://' . $website;
                                        echo '<a href="' . htmlspecialchars($websiteUrl) . '" target="_blank" class="text-blue-600 hover:text-blue-800">' . htmlspecialchars($website) . '</a>';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo number_format($company['active_jobs_count']); ?> Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($company['joined_date']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <!-- View Button -->
                                        <a href="/apex-nexus-portal/admin/company-detail.php?id=<?php echo $company['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm">
                                            View
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" onsubmit="return confirm('Delete this company and associated user account? This action cannot be undone.')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No companies found</p>
                                    <p class="text-sm mt-1">
                                        <?php if (!empty($search)): ?>
                                            Try adjusting your search criteria
                                        <?php else: ?>
                                            No companies have registered yet
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

<?php require_once '../includes/footer.php'; ?>