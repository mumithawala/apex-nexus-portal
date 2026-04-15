<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Search Candidates - Apex Nexus";
require_once '../includes/header.php';

$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get company record
$stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company['id'] ?? null;

// Get search filters
$keyword = clean($_GET['keyword'] ?? '');
$city = clean($_GET['city'] ?? '');
$experienceMin = clean($_GET['experience_min'] ?? '');
$experienceMax = clean($_GET['experience_max'] ?? '');
$jobType = clean($_GET['job_type'] ?? '');
$noticePeriod = clean($_GET['notice_period'] ?? '');

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build search query
try {
    $whereConditions = ["c.is_deleted = 0"];
    $params = [];
    
    if (!empty($keyword)) {
        $whereConditions[] = "(c.full_name LIKE ? OR c.skills LIKE ? OR c.current_job_title LIKE ? OR c.current_company LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    
    if (!empty($city)) {
        $whereConditions[] = "c.city LIKE ?";
        $params[] = "%$city%";
    }
    
    if (!empty($experienceMin)) {
        $whereConditions[] = "c.total_experience >= ?";
        $params[] = $experienceMin;
    }
    
    if (!empty($experienceMax)) {
        $whereConditions[] = "c.total_experience <= ?";
        $params[] = $experienceMax;
    }
    
    if (!empty($jobType)) {
        $whereConditions[] = "c.job_type = ?";
        $params[] = $jobType;
    }
    
    if (!empty($noticePeriod)) {
        $whereConditions[] = "c.notice_period = ?";
        $params[] = $noticePeriod;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates c $whereClause");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    
    // Fetch candidates
    $stmt = $pdo->prepare("
        SELECT c.* FROM candidates c 
        $whereClause
        ORDER BY c.created_at DESC 
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $candidates = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Candidates search error: " . $e->getMessage());
    $candidates = [];
    $totalCount = 0;
    $totalPages = 0;
}
?>

<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/company-nav.css">
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/company-modern.css">

<!-- Modern Company Navigation -->
<?php include '../includes/company-navbar.php'; ?>

<div class="min-h-screen bg-gray-50 p-6 lg:p-8 mt-20">
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-2">
        Search Candidate Pool
      </h1>
      <p class="text-gray-600">Find the right candidate from <?php echo number_format($totalCount); ?> profiles</p>
    </div>

    <!-- Search Bar -->
    <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 mb-6 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
      <form method="GET" class="flex flex-wrap gap-4 items-end relative z-10">
        <div class="flex-1 min-w-64">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <input type="text" name="keyword" placeholder="Search by name, skills, job title, company..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                   value="<?php echo htmlspecialchars($keyword); ?>">
          </div>
        </div>
        
        <div class="w-48">
          <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
          <input type="text" name="city" placeholder="Location"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                 value="<?php echo htmlspecialchars($city); ?>">
        </div>
        
        <div class="flex gap-2">
          <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
            Search
          </button>
          <a href="<?php echo $COMPANY_URL; ?>/search-candidates.php" 
             class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
            Reset
          </a>
        </div>
      </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Left Sidebar - Filters -->
      <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 sticky top-24 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 relative z-10">Filters</h3>
          
          <form method="GET" class="space-y-6 relative z-10">
            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
            <input type="hidden" name="city" value="<?php echo htmlspecialchars($city); ?>">
            
            <!-- Experience Range -->
            <div>
              <h4 class="text-sm font-medium text-gray-700 mb-3">Experience Range</h4>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-600 mb-1">Min Years</label>
                  <input type="number" name="experience_min" min="0" max="50"
                         class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-sm"
                         placeholder="0"
                         value="<?php echo htmlspecialchars($experienceMin); ?>">
                </div>
                <div>
                  <label class="block text-xs text-gray-600 mb-1">Max Years</label>
                  <input type="number" name="experience_max" min="0" max="50"
                         class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-sm"
                         placeholder="50"
                         value="<?php echo htmlspecialchars($experienceMax); ?>">
                </div>
              </div>
            </div>
            
            <!-- Job Type -->
            <div>
              <h4 class="text-sm font-medium text-gray-700 mb-3">Job Type Preference</h4>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="checkbox" name="job_type" value="full-time" 
                         <?php echo $jobType === 'full-time' ? 'checked' : ''; ?>
                         class="rounded border-gray-300 text-blue-600">
                  <span class="ml-2 text-sm text-gray-700">Full-time</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="job_type" value="part-time"
                         <?php echo $jobType === 'part-time' ? 'checked' : ''; ?>
                         class="rounded border-gray-300 text-blue-600">
                  <span class="ml-2 text-sm text-gray-700">Part-time</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="job_type" value="contract"
                         <?php echo $jobType === 'contract' ? 'checked' : ''; ?>
                         class="rounded border-gray-300 text-blue-600">
                  <span class="ml-2 text-sm text-gray-700">Contract</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="job_type" value="internship"
                         <?php echo $jobType === 'internship' ? 'checked' : ''; ?>
                         class="rounded border-gray-300 text-blue-600">
                  <span class="ml-2 text-sm text-gray-700">Internship</span>
                </label>
              </div>
            </div>
            
            <!-- Notice Period -->
            <div>
              <h4 class="text-sm font-medium text-gray-700 mb-3">Notice Period</h4>
              <select name="notice_period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-sm">
                <option value="">Any</option>
                <option value="immediate" <?php echo $noticePeriod === 'immediate' ? 'selected' : ''; ?>>Immediate</option>
                <option value="15 days" <?php echo $noticePeriod === '15 days' ? 'selected' : ''; ?>>15 days</option>
                <option value="30 days" <?php echo $noticePeriod === '30 days' ? 'selected' : ''; ?>>30 days</option>
                <option value="60 days" <?php echo $noticePeriod === '60 days' ? 'selected' : ''; ?>>60 days</option>
                <option value="90 days" <?php echo $noticePeriod === '90 days' ? 'selected' : ''; ?>>90 days</option>
              </select>
            </div>
            
            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
              Apply Filters
            </button>
          </form>
        </div>
      </div>

      <!-- Right Column - Results -->
      <div class="lg:col-span-3">
        <?php if (count($candidates) > 0): ?>
          <!-- Results Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($candidates as $candidate): ?>
              <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-5 hover:shadow-2xl transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-2xl"></div>
                <!-- Header -->
                <div class="flex items-start gap-3 mb-4 relative z-10">
                  <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                    <?php echo strtoupper(substr($candidate['full_name'], 0, 1)); ?>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                    <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($candidate['current_job_title'] ?? 'Not specified'); ?></p>
                    <?php if ($candidate['current_company']): ?>
                      <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($candidate['current_company']); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Location -->
                <div class="text-sm text-gray-600 mb-3 relative z-10">
                  <?php if ($candidate['city']): ?>
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <?php echo htmlspecialchars($candidate['city']); ?>
                    <?php if ($candidate['state']): ?>
                      , <?php echo htmlspecialchars($candidate['state']); ?>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
                
                <!-- Tags -->
                <div class="flex flex-wrap gap-2 mb-4 relative z-10">
                  <?php if ($candidate['total_experience']): ?>
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium"><?php echo $candidate['total_experience']; ?> yrs</span>
                  <?php endif; ?>
                  <?php if ($candidate['job_type']): ?>
                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium"><?php echo htmlspecialchars($candidate['job_type']); ?></span>
                  <?php endif; ?>
                  <?php if ($candidate['notice_period']): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium"><?php echo htmlspecialchars($candidate['notice_period']); ?></span>
                  <?php endif; ?>
                </div>
                
                <!-- Skills -->
                <?php 
                $skills = array_filter(array_map('trim', explode(',', $candidate['skills'] ?? '')));
                if (!empty($skills)): 
                  $displaySkills = array_slice($skills, 0, 4);
                ?>
                  <div class="flex flex-wrap gap-1 mb-4 relative z-10">
                    <?php foreach ($displaySkills as $skill): ?>
                      <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200 transition-colors"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($skills) > 4): ?>
                      <span class="text-xs text-gray-500">+<?php echo count($skills) - 4; ?> more</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                
                <!-- Action -->
                <div class="flex gap-2 relative z-10">
                  <a href="<?php echo $COMPANY_URL; ?>/search-candidates.php?action=view_profile&id=<?php echo $candidate['id']; ?>" 
                     class="flex-1 text-center px-3 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 text-sm font-medium shadow-md">
                    View Profile
                  </a>
                  <?php if ($candidate['resume']): ?>
                    <a href="<?php echo $ASSETS_URL; ?>/uploads/resumes/<?php echo htmlspecialchars($candidate['resume']); ?>" 
                       target="_blank" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium">
                      Resume
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-4 mt-6 relative overflow-hidden">
              <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
              <div class="flex justify-between items-center relative z-10">
                <div class="text-sm text-gray-700">
                  Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalCount); ?> 
                  of <?php echo $totalCount; ?> candidates
                </div>
                <div class="flex gap-2">
                  <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?><?php echo !empty($experienceMin) ? '&experience_min=' . urlencode($experienceMin) : ''; ?><?php echo !empty($experienceMax) ? '&experience_max=' . urlencode($experienceMax) : ''; ?><?php echo !empty($jobType) ? '&job_type=' . urlencode($jobType) : ''; ?><?php echo !empty($noticePeriod) ? '&notice_period=' . urlencode($noticePeriod) : ''; ?>" 
                       class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                      Previous
                    </a>
                  <?php endif; ?>
                  
                  <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?><?php echo !empty($experienceMin) ? '&experience_min=' . urlencode($experienceMin) : ''; ?><?php echo !empty($experienceMax) ? '&experience_max=' . urlencode($experienceMax) : ''; ?><?php echo !empty($jobType) ? '&job_type=' . urlencode($jobType) : ''; ?><?php echo !empty($noticePeriod) ? '&notice_period=' . urlencode($noticePeriod) : ''; ?>" 
                       class="px-3 py-1 rounded-lg <?php echo $i === $page ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors'; ?>">
                      <?php echo $i; ?>
                    </a>
                  <?php endfor; ?>
                  
                  <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?><?php echo !empty($experienceMin) ? '&experience_min=' . urlencode($experienceMin) : ''; ?><?php echo !empty($experienceMax) ? '&experience_max=' . urlencode($experienceMax) : ''; ?><?php echo !empty($jobType) ? '&job_type=' . urlencode($jobType) : ''; ?><?php echo !empty($noticePeriod) ? '&notice_period=' . urlencode($noticePeriod) : ''; ?>" 
                       class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                      Next
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <!-- Empty State -->
          <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-12 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-pink-400/10 rounded-full blur-2xl"></div>
            <div class="relative z-10">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full mx-auto mb-6 flex items-center justify-center">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">No candidates found</h3>
            <p class="text-gray-600">Try different keywords or adjust your search criteria</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
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
        <a href="<?php echo $COMPANY_URL; ?>/post-job.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 4v16m8-8H4"/>
            </svg>
            <span>Post Job</span>
        </a>
        <a href="<?php echo $COMPANY_URL; ?>/manage-jobs.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="9" x2="15" y2="9"/>
                <line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
            <span>Manage Jobs</span>
        </a>
        <a href="<?php echo $COMPANY_URL; ?>/applicants.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-4-4h-1v-4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v4h-1"/>
            </svg>
            <span>View Applicants</span>
        </a>
        <a href="<?php echo $COMPANY_URL; ?>/profile.php" class="quick-action">
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