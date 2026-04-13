<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Applicants - Apex Nexus";
require_once '../includes/header.php';

$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get company record
$stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company['id'] ?? null;

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $newStatus = clean($_POST['status'] ?? '');
    
    if ($applicationId > 0 && in_array($newStatus, ['applied', 'reviewed', 'shortlisted', 'rejected'])) {
        try {
            // Verify application belongs to this company
            $stmt = $pdo->prepare("
                SELECT a.id FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                WHERE a.id = ? AND j.company_id = ? AND a.is_deleted = 0
            ");
            $stmt->execute([$applicationId, $companyId]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $applicationId]);
                setFlash('success', 'Application status updated successfully');
            }
        } catch (PDOException $e) {
            error_log("Status update error: " . $e->getMessage());
            setFlash('error', 'Failed to update status');
        }
    }
    redirect('/apex-nexus-portal/company/applicants.php?' . http_build_query($_GET));
}

// Handle bulk status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['selected_applications'])) {
    $bulkAction = clean($_POST['bulk_action']);
    $applicationIds = array_map('intval', $_POST['selected_applications']);
    
    if (in_array($bulkAction, ['reviewed', 'shortlisted', 'rejected'])) {
        try {
            $placeholders = str_repeat('?,', count($applicationIds) - 1) . '?';
            $stmt = $pdo->prepare("
                UPDATE applications a 
                JOIN jobs j ON a.job_id = j.id 
                SET a.status = ?, a.updated_at = NOW() 
                WHERE a.id IN ($placeholders) AND j.company_id = ? AND a.is_deleted = 0
            ");
            $params = array_merge([$bulkAction], $applicationIds, [$companyId]);
            $stmt->execute($params);
            setFlash('success', 'Applications updated successfully');
        } catch (PDOException $e) {
            error_log("Bulk update error: " . $e->getMessage());
            setFlash('error', 'Failed to update applications');
        }
    }
    redirect('/apex-nexus-portal/company/applicants.php?' . http_build_query($_GET));
}

// Get filters
$jobFilter = (int) ($_GET['job_id'] ?? 0);
$statusFilter = clean($_GET['status'] ?? '');
$searchFilter = clean($_GET['search'] ?? '');

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Fetch applications
try {
    $whereConditions = ["j.company_id = ?", "a.is_deleted = 0", "j.is_deleted = 0"];
    $params = [$companyId];
    
    if ($jobFilter > 0) {
        $whereConditions[] = "a.job_id = ?";
        $params[] = $jobFilter;
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "a.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($searchFilter)) {
        $whereConditions[] = "(c.full_name LIKE ? OR c.email LIKE ?)";
        $params[] = "%$searchFilter%";
        $params[] = "%$searchFilter%";
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Get total count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN candidates c ON a.candidate_id = c.id
        $whereClause
    ");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    
    // Fetch applications
    $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.city as job_city,
               c.full_name, c.email, c.phone, c.total_experience, c.skills
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN candidates c ON a.candidate_id = c.id
        $whereClause
        ORDER BY a.created_at DESC 
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
    
    // Get company jobs for filter dropdown
    $stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE company_id = ? AND is_deleted = 0 ORDER BY title");
    $stmt->execute([$companyId]);
    $jobs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Applications fetch error: " . $e->getMessage());
    $applications = [];
    $jobs = [];
    $totalCount = 0;
    $totalPages = 0;
}
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company.css">

<div class="flex min-h-screen bg-gray-50">
  <main class="flex-1 p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Applicants</h1>
          <p class="text-gray-600">Review and manage job applications.</p>
        </div>
        <div class="flex gap-3">
          <button type="button" id="listViewBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            List View
          </button>
          <button type="button" id="kanbanViewBtn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            Kanban View
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
      <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-64">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input type="text" name="search" placeholder="Search by candidate name or email"
                 class="search-input"
                 value="<?php echo htmlspecialchars($searchFilter); ?>">
        </div>
        
        <div class="w-48">
          <label class="block text-sm font-medium text-gray-700 mb-2">Job</label>
          <select name="job_id" class="search-input">
            <option value="">All Jobs</option>
            <?php foreach ($jobs as $job): ?>
              <option value="<?php echo $job['id']; ?>" 
                      <?php echo $jobFilter === $job['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($job['title']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="w-48">
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select name="status" class="search-input">
            <option value="">All Status</option>
            <option value="applied" <?php echo $statusFilter === 'applied' ? 'selected' : ''; ?>>Applied</option>
            <option value="reviewed" <?php echo $statusFilter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
            <option value="shortlisted" <?php echo $statusFilter === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
        </div>
        
        <div class="flex gap-2">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            Filter
          </button>
          <a href="/apex-nexus-portal/company/applicants.php" 
             class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
            Clear
          </a>
        </div>
      </form>
    </div>

    <!-- List View -->
    <div id="listView">
      <?php if (count($applications) > 0): ?>
        <!-- Bulk Actions -->
        <form method="POST" id="bulkActionsForm">
          <input type="hidden" name="bulk_action" id="bulkActionInput">
          
          <div class="bg-white rounded-2xl border border-gray-100 mb-6 p-4">
            <div class="flex items-center gap-4">
              <input type="checkbox" id="selectAll" class="rounded border-gray-300">
              <label for="selectAll" class="text-sm text-gray-700">Select All</label>
              
              <div class="flex gap-2 ml-auto">
                <button type="button" onclick="performBulkAction('reviewed')" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                  Mark as Reviewed
                </button>
                <button type="button" onclick="performBulkAction('shortlisted')" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                  Mark as Shortlisted
                </button>
                <button type="button" onclick="performBulkAction('rejected')" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded text-red-600 hover:bg-red-50">
                  Reject Selected
                </button>
              </div>
            </div>
          </div>

          <!-- Applications Table -->
          <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="w-12">
                      <input type="checkbox" class="rounded border-gray-300">
                    </th>
                    <th>Candidate</th>
                    <th>Applied For</th>
                    <th>Experience</th>
                    <th>Applied Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($applications as $app): ?>
                    <tr>
                      <td>
                        <input type="checkbox" name="selected_applications[]" 
                               value="<?php echo $app['id']; ?>" 
                               class="application-checkbox rounded border-gray-300">
                      </td>
                      <td>
                        <div class="flex items-center gap-3">
                          <div class="avatar" style="width: 40px; height: 40px; font-size: 14px;">
                            <?php echo strtoupper(substr($app['full_name'], 0, 1)); ?>
                          </div>
                          <div>
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($app['full_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($app['email']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($app['job_title']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($app['job_city']); ?></div>
                      </td>
                      <td>
                        <span class="tag"><?php echo $app['total_experience'] ?? '0'; ?> yrs</span>
                      </td>
                      <td><?php echo formatDate($app['created_at']); ?></td>
                      <td>
                        <span class="badge badge-<?php echo $app['status']; ?>">
                          <?php echo ucfirst($app['status']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="flex items-center gap-2">
                          <a href="/apex-nexus-portal/company/applicant-detail.php?id=<?php echo $app['id']; ?>" 
                             class="text-blue-600 hover:text-blue-700 text-sm">
                            View Profile
                          </a>
                          
                          <!-- Status Dropdown -->
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <select name="status" onchange="this.form.submit()" 
                                    class="text-sm border border-gray-300 rounded px-2 py-1">
                              <option value="">Change Status</option>
                              <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                              <option value="shortlisted" <?php echo $app['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                              <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </form>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="bg-white rounded-2xl border border-gray-100 p-4 mt-6">
            <div class="flex justify-between items-center">
              <div class="text-sm text-gray-700">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalCount); ?> 
                of <?php echo $totalCount; ?> results
              </div>
              <div class="flex gap-2">
                <?php if ($page > 1): ?>
                  <a href="?page=<?php echo $page - 1; ?><?php echo !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : ''; ?><?php echo $jobFilter > 0 ? '&job_id=' . $jobFilter : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                     class="px-3 py-1 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Previous
                  </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                  <a href="?page=<?php echo $i; ?><?php echo !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : ''; ?><?php echo $jobFilter > 0 ? '&job_id=' . $jobFilter : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                     class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                  </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                  <a href="?page=<?php echo $page + 1; ?><?php echo !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : ''; ?><?php echo $jobFilter > 0 ? '&job_id=' . $jobFilter : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                     class="px-3 py-1 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Next
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No applications found</h3>
          <p class="text-gray-600">
            <?php if (!empty($searchFilter) || $jobFilter > 0 || !empty($statusFilter)): ?>
              Try adjusting your search or filter criteria
            <?php else: ?>
              No applications have been submitted yet
            <?php endif; ?>
          </p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Kanban View (Hidden by default) -->
    <div id="kanbanView" class="hidden">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <?php 
        $statuses = ['applied', 'reviewed', 'shortlisted', 'rejected'];
        $colors = ['blue', 'amber', 'green', 'red'];
        $statusCounts = array_count_values(array_column($applications, 'status'));
        
        foreach ($statuses as $index => $status): 
          $statusApplications = array_filter($applications, fn($app) => $app['status'] === $status);
        ?>
          <div class="kanban-col">
            <div class="kanban-col-header" style="border-color: <?php echo $colors[$index] === 'blue' ? '#3B82F6' : ($colors[$index] === 'amber' ? '#F59E0B' : ($colors[$index] === 'green' ? '#10B981' : '#EF4444')); ?>;">
              <div class="flex justify-between items-center">
                <span class="capitalize"><?php echo $status; ?></span>
                <span class="text-sm text-gray-500"><?php echo $statusCounts[$status] ?? 0; ?></span>
              </div>
            </div>
            
            <div class="space-y-2">
              <?php foreach ($statusApplications as $app): ?>
                <div class="kanban-card">
                  <div class="flex items-center gap-2 mb-2">
                    <div class="avatar" style="width: 32px; height: 32px; font-size: 12px;">
                      <?php echo strtoupper(substr($app['full_name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($app['full_name']); ?></div>
                      <div class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($app['job_title']); ?></div>
                    </div>
                  </div>
                  <div class="text-xs text-gray-500"><?php echo formatDate($app['created_at']); ?></div>
                  <div class="mt-2">
                    <a href="/apex-nexus-portal/company/applicant-detail.php?id=<?php echo $app['id']; ?>" 
                       class="text-blue-600 hover:text-blue-700 text-xs">
                      View Details
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </main>
</div>

<script>
// View Toggle
document.getElementById('listViewBtn').addEventListener('click', () => {
  document.getElementById('listView').classList.remove('hidden');
  document.getElementById('kanbanView').classList.add('hidden');
  document.getElementById('listViewBtn').classList.add('bg-blue-600', 'text-white');
  document.getElementById('listViewBtn').classList.remove('border', 'border-gray-300', 'text-gray-700');
  document.getElementById('kanbanViewBtn').classList.remove('bg-blue-600', 'text-white');
  document.getElementById('kanbanViewBtn').classList.add('border', 'border-gray-300', 'text-gray-700');
});

document.getElementById('kanbanViewBtn').addEventListener('click', () => {
  document.getElementById('listView').classList.add('hidden');
  document.getElementById('kanbanView').classList.remove('hidden');
  document.getElementById('kanbanViewBtn').classList.add('bg-blue-600', 'text-white');
  document.getElementById('kanbanViewBtn').classList.remove('border', 'border-gray-300', 'text-gray-700');
  document.getElementById('listViewBtn').classList.remove('bg-blue-600', 'text-white');
  document.getElementById('listViewBtn').classList.add('border', 'border-gray-300', 'text-gray-700');
});

// Select All Checkbox
document.getElementById('selectAll').addEventListener('change', (e) => {
  const checkboxes = document.querySelectorAll('.application-checkbox');
  checkboxes.forEach(checkbox => checkbox.checked = e.target.checked);
});

// Update Select All when individual checkboxes change
document.querySelectorAll('.application-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', () => {
    const allCheckboxes = document.querySelectorAll('.application-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.application-checkbox:checked');
    document.getElementById('selectAll').checked = allCheckboxes.length === checkedCheckboxes.length;
  });
});

// Bulk Actions
function performBulkAction(action) {
  const selectedApplications = document.querySelectorAll('.application-checkbox:checked');
  if (selectedApplications.length === 0) {
    alert('Please select at least one application');
    return;
  }
  
  if (confirm(`Are you sure you want to mark ${selectedApplications.length} application(s) as ${action}?`)) {
    document.getElementById('bulkActionInput').value = action;
    document.getElementById('bulkActionsForm').submit();
  }
}

function toggleQuickActions() {
    const menu = document.getElementById('quickActionsMenu');
    menu.classList.toggle('hidden');
    
    if (!menu.classList.contains('hidden')) {
        setTimeout(() => {
            document.addEventListener('click', closeQuickActions);
        }, 100);
    }
}

function closeQuickActions(e) {
    const menu = document.getElementById('quickActionsMenu');
    const button = document.querySelector('.fab');
    
    if (!menu.contains(e.target) && !button.contains(e.target)) {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeQuickActions);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>