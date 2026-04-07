<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
requireRole('candidate');
$pageTitle = "Search Jobs - Apex Nexus";
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

// Get search parameters
$keyword = $_GET['keyword'] ?? '';
$location = $_GET['location'] ?? '';
$employment_type = $_GET['employment_type'] ?? '';
$work_mode = $_GET['work_mode'] ?? '';
$experience = $_GET['experience'] ?? '';
$salary_min = $_GET['salary_min'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = ["j.status = 'active'", "j.is_deleted = 0", "(j.deadline IS NULL OR j.deadline >= CURDATE())"];
$params = [];

if (!empty($keyword)) {
    $where[] = "(j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if (!empty($location)) {
    $where[] = "(j.location LIKE ? OR j.city LIKE ? OR j.state LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
    $params[] = "%$location%";
}

if (!empty($employment_type)) {
    $where[] = "j.employment_type = ?";
    $params[] = $employment_type;
}

if (!empty($work_mode)) {
    $where[] = "j.work_mode = ?";
    $params[] = $work_mode;
}

if (!empty($experience)) {
    $where[] = "j.experience_required = ?";
    $params[] = $experience;
}

if (!empty($salary_min)) {
    $where[] = "j.salary_min >= ?";
    $params[] = $salary_min;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Build ORDER BY clause
$orderBy = "ORDER BY j.created_at DESC";
switch ($sort) {
    case 'salary_high':
        $orderBy = "ORDER BY j.salary_min DESC";
        break;
    case 'salary_low':
        $orderBy = "ORDER BY j.salary_min ASC";
        break;
    case 'relevance':
        if (!empty($keyword)) {
            $orderBy = "ORDER BY CASE WHEN j.title LIKE ? THEN 1 ELSE 2 END, j.created_at DESC";
            array_unshift($params, "%$keyword%");
        }
        break;
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM jobs j JOIN companies c ON j.company_id = c.id $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalJobs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalJobs / $perPage);

// Get jobs with pagination
$sql = "SELECT j.*, c.company_name, c.city as company_city, c.state as company_state 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        $whereClause 
        $orderBy 
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$executeParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($executeParams);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get already applied job IDs
$appliedJobIds = [];
if ($candidateId) {
    $stmt = $pdo->prepare("SELECT job_id FROM applications WHERE candidate_id = ? AND is_deleted = 0");
    $stmt->execute([$candidateId]);
    $appliedJobIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'job_id');
}
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input type="text" name="keyword" placeholder="Job title, skills, keywords..." 
                           value="<?php echo htmlspecialchars($keyword); ?>"
                           class="search-input pl-10">
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input type="text" name="location" placeholder="Location, city..." 
                           value="<?php echo htmlspecialchars($location); ?>"
                           class="search-input pl-10">
                </div>
            </div>
            
            <!-- Hidden fields for pagination -->
            <input type="hidden" name="page" value="1">
            
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Search Jobs
            </button>
        </form>
        
        <!-- Filter Pills -->
        <div class="mt-4 space-y-3">
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 mr-2">Employment Type:</span>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['employment_type' => '', 'page' => 1])); ?>" 
                   class="tag <?php echo empty($employment_type) ? 'tag-blue' : ''; ?>">All</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['employment_type' => 'Full-time', 'page' => 1])); ?>" 
                   class="tag <?php echo $employment_type === 'Full-time' ? 'tag-blue' : ''; ?>">Full-time</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['employment_type' => 'Part-time', 'page' => 1])); ?>" 
                   class="tag <?php echo $employment_type === 'Part-time' ? 'tag-blue' : ''; ?>">Part-time</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['employment_type' => 'Contract', 'page' => 1])); ?>" 
                   class="tag <?php echo $employment_type === 'Contract' ? 'tag-blue' : ''; ?>">Contract</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['employment_type' => 'Internship', 'page' => 1])); ?>" 
                   class="tag <?php echo $employment_type === 'Internship' ? 'tag-blue' : ''; ?>">Internship</a>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 mr-2">Work Mode:</span>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => '', 'page' => 1])); ?>" 
                   class="tag <?php echo empty($work_mode) ? 'tag-green' : ''; ?>">All</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => 'On-site', 'page' => 1])); ?>" 
                   class="tag <?php echo $work_mode === 'On-site' ? 'tag-green' : ''; ?>">On-site</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => 'Remote', 'page' => 1])); ?>" 
                   class="tag <?php echo $work_mode === 'Remote' ? 'tag-green' : ''; ?>">Remote</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => 'Hybrid', 'page' => 1])); ?>" 
                   class="tag <?php echo $work_mode === 'Hybrid' ? 'tag-green' : ''; ?>">Hybrid</a>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 mr-2">Experience:</span>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '', 'page' => 1])); ?>" 
                   class="tag <?php echo empty($experience) ? 'tag-blue' : ''; ?>">Any</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '0-1 years', 'page' => 1])); ?>" 
                   class="tag <?php echo $experience === '0-1 years' ? 'tag-blue' : ''; ?>">0-1yr</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '1-3 years', 'page' => 1])); ?>" 
                   class="tag <?php echo $experience === '1-3 years' ? 'tag-blue' : ''; ?>">1-3yr</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '3-5 years', 'page' => 1])); ?>" 
                   class="tag <?php echo $experience === '3-5 years' ? 'tag-blue' : ''; ?>">3-5yr</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '5+ years', 'page' => 1])); ?>" 
                   class="tag <?php echo $experience === '5+ years' ? 'tag-blue' : ''; ?>">5+yr</a>
            </div>
        </div>
    </div>

    <!-- Results Area -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Left Sidebar Filters -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl p-6 border border-gray-100 lg:sticky lg:top-6">
                <h3 class="font-semibold text-gray-800 mb-4">Filters</h3>
                
                <form method="GET" class="space-y-4">
                    <!-- Preserve existing search parameters -->
                    <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                    <input type="hidden" name="employment_type" value="<?php echo htmlspecialchars($employment_type); ?>">
                    <input type="hidden" name="work_mode" value="<?php echo htmlspecialchars($work_mode); ?>">
                    <input type="hidden" name="experience" value="<?php echo htmlspecialchars($experience); ?>">
                    
                    <!-- Salary Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salary Range (<?php echo htmlspecialchars($currency ?? 'USD'); ?>)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="salary_min" placeholder="Min" 
                                   value="<?php echo htmlspecialchars($salary_min); ?>"
                                   class="search-input text-sm">
                            <input type="number" name="salary_max" placeholder="Max" 
                                   value="<?php echo htmlspecialchars($_GET['salary_max'] ?? ''); ?>"
                                   class="search-input text-sm">
                        </div>
                    </div>
                    
                    <!-- Posted Within -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Posted Within</label>
                        <select name="posted_within" class="search-input text-sm">
                            <option value="">Any time</option>
                            <option value="24" <?php echo ($_GET['posted_within'] ?? '') === '24' ? 'selected' : ''; ?>>Last 24 hours</option>
                            <option value="168" <?php echo ($_GET['posted_within'] ?? '') === '168' ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="720" <?php echo ($_GET['posted_within'] ?? '') === '720' ? 'selected' : ''; ?>>Last 30 days</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Apply Filters
                    </button>
                    
                    <a href="?" class="block text-center text-blue-600 hover:text-blue-700 text-sm">
                        Clear All
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Right Jobs List -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                
                <!-- Results Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            <?php 
                            if (!empty($keyword) || !empty($location)) {
                                echo "Showing " . $totalJobs . " jobs";
                                if (!empty($keyword)) echo " for '" . htmlspecialchars($keyword) . "'";
                                if (!empty($location)) echo " in " . htmlspecialchars($location);
                            } else {
                                echo "All Jobs (" . $totalJobs . ")";
                            }
                            ?>
                        </h2>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Sort by:</label>
                        <select onchange="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['sort' => '__SORT__'])); ?>'.replace('__SORT__', this.value)" 
                                class="search-input text-sm">
                            <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                            <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                            <option value="salary_high" <?php echo $sort === 'salary_high' ? 'selected' : ''; ?>>Salary (High-Low)</option>
                            <option value="salary_low" <?php echo $sort === 'salary_low' ? 'selected' : ''; ?>>Salary (Low-High)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Jobs List -->
                <?php if (empty($jobs)): ?>
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-gray-500">No jobs found matching your criteria.</p>
                        <p class="text-sm text-gray-400 mt-2">Try adjusting your filters or search terms.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <div class="flex gap-4">
                                    <!-- Company Logo -->
                                    <div class="flex-shrink-0">
                                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600"><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Job Details -->
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                                                <div class="text-sm text-gray-600 mb-2">
                                                    <?php echo htmlspecialchars($job['company_name']); ?> 
                                                    <span class="mx-1">·</span>
                                                    <?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="text-right">
                                                <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
                                                    <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($job['salary']); ?></div>
                                                <?php else: ?>
                                                    <div class="text-sm text-gray-500">Salary not disclosed</div>
                                                <?php endif; ?>
                                                <div class="text-xs text-gray-500"><?php echo timeAgo($job['created_at']); ?></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tags -->
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span class="tag tag-blue"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                            <span class="tag tag-green"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                                            <span class="tag"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                                        </div>
                                        
                                        <!-- Description -->
                                        <div class="text-sm text-gray-600 mb-3">
                                            <?php echo htmlspecialchars(substr(strip_tags($job['description']), 0, 120)); ?>
                                            <?php if (strlen(strip_tags($job['description'])) > 120): ?>...<?php endif; ?>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex gap-2">
                                            <?php if (in_array($job['id'], $appliedJobIds)): ?>
                                                <span class="bg-green-50 text-green-600 border border-green-200 px-3 py-1 rounded-lg text-sm font-medium">
                                                    Applied ?
                                                </span>
                                            <?php else: ?>
                                                <a href="/apex-nexus-portal/candidate/apply.php?job_id=<?php echo $job['id']; ?>" 
                                                   class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                                    Apply Now
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $job['id']; ?>" 
                                               class="border border-gray-300 text-gray-700 px-3 py-1 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-6 flex justify-center">
                            <nav class="flex items-center gap-1">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                
                                <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="px-3 py-2 rounded-lg <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

  </main>
</div>

<?php require_once '../includes/footer.php'; ?>