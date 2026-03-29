<?php
/**
 * Admin Candidates Management
 */

$pageTitle = "Manage Candidates - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $candidateId = (int)($_POST['candidate_id'] ?? 0);
    
    if ($action === 'delete' && $candidateId > 0) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Get user_id for this candidate
            $stmt = $pdo->prepare("SELECT user_id FROM candidates WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$candidateId]);
            $candidate = $stmt->fetch();
            
            if ($candidate && $candidate['user_id']) {
                // Soft delete candidate
                if (softDelete($pdo, 'candidates', $candidateId)) {
                    // Soft delete associated user
                    softDelete($pdo, 'users', $candidate['user_id']);
                    setFlash('success', 'Candidate deleted successfully');
                } else {
                    setFlash('error', 'Failed to delete candidate');
                }
            } else {
                setFlash('error', 'Candidate not found');
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Candidate delete error: " . $e->getMessage());
            setFlash('error', 'Delete failed. Please try again.');
        }
    }
    
    redirect('/apex-nexus-portal/admin/candidates.php');
}

// Fetch candidates with search
$search = clean($_GET['search'] ?? '');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Build query
    $whereConditions = ["c.is_deleted = 0", "u.is_deleted = 0"];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Fetch candidates with user details
    $candidatesQuery = "
        SELECT c.*, u.name as user_name, u.email as user_email, u.created_at as joined_date,
               (SELECT COUNT(*) FROM applications WHERE candidate_id = c.id AND is_deleted = 0) as applications_count
        FROM candidates c 
        LEFT JOIN users u ON c.user_id = u.id 
        $whereClause 
        ORDER BY c.created_at DESC
    ";
    $stmt = $pdo->prepare($candidatesQuery);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Candidates fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load candidates data');
    $candidates = [];
}

// Include navbar
require_once '../includes/navbar.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manage Candidates</h1>
        <p class="text-gray-600 mt-2">View and manage all registered candidates</p>
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
                        placeholder="Search by name or email..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full"
                    >
                </div>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Search
            </button>
            
            <?php if (!empty($search)): ?>
                <a href="/apex-nexus-portal/admin/candidates.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Candidates Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skills</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resume</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($candidates) > 0): ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($candidate['user_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($candidate['user_email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $skills = $candidate['skills'] ?? '';
                                    if (!empty($skills) && $skills !== 'N/A') {
                                        echo htmlspecialchars(substr($skills, 0, 50));
                                        if (strlen($skills) > 50) {
                                            echo '...';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $resume = $candidate['resume'] ?? '';
                                    if (!empty($resume) && $resume !== 'N/A') {
                                        echo '<a href="/apex-nexus-portal/assets/uploads/resumes/' . htmlspecialchars($resume) . '" target="_blank" class="text-blue-600 hover:text-blue-800 inline-flex items-center">';
                                        echo '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
                                        echo '</svg>';
                                        echo 'Download';
                                        echo '</a>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Not uploaded</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo number_format($candidate['applications_count']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($candidate['joined_date']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <!-- View Button -->
                                        <a href="/apex-nexus-portal/admin/candidate-detail.php?id=<?php echo $candidate['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm">
                                            View
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" onsubmit="return confirm('Delete this candidate and associated user account? This action cannot be undone.')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No candidates found</p>
                                    <p class="text-sm mt-1">
                                        <?php if (!empty($search)): ?>
                                            Try adjusting your search criteria
                                        <?php else: ?>
                                            No candidates have registered yet
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