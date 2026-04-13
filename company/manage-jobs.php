<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Manage Jobs - Apex Nexus";
require_once '../includes/header.php';

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

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company.css">

<div class="flex min-h-screen bg-gray-50">
  <main class="flex-1 p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">My Job Postings</h1>
          <p class="text-gray-600">Manage your active job listings and track applications.</p>
        </div>
        <a href="/apex-nexus-portal/company/post-job.php" 
           class="bg-blue-600 text-white rounded-xl px-5 py-2.5 hover:bg-blue-700 transition-colors">
          Post New Job
        </a>
      </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="bg-white rounded-2xl border border-gray-100 mb-6">
      <div class="flex space-x-8 px-6 border-b border-gray-200">
        <a href="/apex-nexus-portal/company/manage-jobs.php" 
           class="py-4 px-1 border-b-2 font-medium text-sm <?php echo empty($statusFilter) ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
          All Jobs <?php echo !empty($statusFilter) ? '' : '(' . array_sum($statusCounts) . ')'; ?>
        </a>
        <a href="/apex-nexus-portal/company/manage-jobs.php?status=active" 
           class="py-4 px-1 border-b-2 font-medium text-sm <?php echo $statusFilter === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
          Active <?php echo $statusFilter === 'active' ? '(' . ($statusCounts['active'] ?? 0) . ')' : ''; ?>
        </a>
        <a href="/apex-nexus-portal/company/manage-jobs.php?status=pending" 
           class="py-4 px-1 border-b-2 font-medium text-sm <?php echo $statusFilter === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
          Pending <?php echo $statusFilter === 'pending' ? '(' . ($statusCounts['pending'] ?? 0) . ')' : ''; ?>
        </a>
        <a href="/apex-nexus-portal/company/manage-jobs.php?status=closed" 
           class="py-4 px-1 border-b-2 font-medium text-sm <?php echo $statusFilter === 'closed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
          Closed <?php echo $statusFilter === 'closed' ? '(' . ($statusCounts['closed'] ?? 0) . ')' : ''; ?>
        </a>
      </div>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white rounded-2xl border border-gray-100">
      <?php if (count($jobs) > 0): ?>
        <div class="overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Job Title</th>
                <th>Location</th>
                <th>Type</th>
                <th>Applications</th>
                <th>Posted</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jobs as $job): ?>
                <tr>
                  <td>
                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                    <div class="text-sm text-gray-500">
                      <span class="tag">
                        <?php echo htmlspecialchars($job['work_mode'] ?? 'On-site'); ?>
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="text-gray-900"><?php echo htmlspecialchars($job['city'] ?? ''); ?></div>
                    <?php if (!empty($job['state'])): ?>
                      <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['state']); ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="tag">
                      <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $job['employment_type'] ?? ''))); ?>
                    </span>
                  </td>
                  <td>
                    <span class="tag tag-blue">
                      <?php echo $job['application_count']; ?> apps
                    </span>
                  </td>
                  <td><?php echo formatDate($job['created_at']); ?></td>
                  <td>
                    <?php if ($job['deadline']): ?>
                      <div class="text-gray-900"><?php echo formatDate($job['deadline']); ?></div>
                      <?php 
                      $daysUntilDeadline = (new DateTime($job['deadline']))->diff(new DateTime())->days;
                      if ($daysUntilDeadline <= 7 && $daysUntilDeadline >= 0): ?>
                        <div class="text-sm text-red-600">Expires in <?php echo $daysUntilDeadline; ?> days</div>
                      <?php elseif ($daysUntilDeadline < 0): ?>
                        <div class="text-sm text-gray-500">Expired</div>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="text-gray-500">No deadline</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-<?php echo $job['status']; ?>">
                      <?php echo ucfirst($job['status']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="flex items-center gap-2">
                      <!-- View Applicants -->
                      <a href="/apex-nexus-portal/company/applicants.php?job_id=<?php echo $job['id']; ?>" 
                         class="text-blue-600 hover:text-blue-700" title="View Applicants">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                      </a>
                      
                      <!-- Edit Job -->
                      <a href="/apex-nexus-portal/company/edit-job.php?id=<?php echo $job['id']; ?>" 
                         class="text-gray-600 hover:text-gray-700" title="Edit Job">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                      </a>
                      
                      <!-- Close Job (if active) -->
                      <?php if ($job['status'] === 'active'): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Close this job posting?')">
                          <input type="hidden" name="action" value="close">
                          <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                          <button type="submit" class="text-amber-600 hover:text-amber-700" title="Close Job">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                          </button>
                        </form>
                      <?php endif; ?>
                      
                      <!-- Delete Job -->
                      <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this job posting permanently?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        <button type="submit" class="text-red-600 hover:text-red-700" title="Delete Job">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No jobs posted yet</h3>
          <p class="text-gray-600 mb-4">Get started by posting your first job opening.</p>
          <a href="/apex-nexus-portal/company/post-job.php" 
             class="bg-blue-600 text-white rounded-lg px-6 py-2 hover:bg-blue-700 transition-colors">
            Post Your First Job
          </a>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Quick Actions Floating Button -->
<div class="quick-actions">
    <button class="fab" onclick="toggleQuickActions()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
    </button>
    
    <div id="quickActionsMenu" class="quick-actions-menu hidden">
        <a href="/apex-nexus-portal/company/post-job.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 4v16m8-8H4"/>
            </svg>
            <span>Post Job</span>
        </a>
        <a href="/apex-nexus-portal/company/manage-jobs.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="9" x2="15" y2="9"/>
                <line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
            <span>Manage Jobs</span>
        </a>
        <a href="/apex-nexus-portal/company/applicants.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-4-4h-1v-4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v4h-1"/>
            </svg>
            <span>View Applicants</span>
        </a>
        <a href="/apex-nexus-portal/company/profile.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Edit Profile</span>
        </a>
    </div>
</div>

<script>
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