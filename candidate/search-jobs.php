<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
require_once '../includes/urls.php';
requireRole('candidate');
$pageTitle = "Search Jobs - Apex Nexus";
require_once '../includes/header.php';
// require_once '../includes/navbar.php';

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

<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate-nav.css">
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <!-- Search Hero Section -->
    <div class="search-hero-section">
        <div class="hero-background">
            <div class="hero-gradient-orb orb-1"></div>
            <div class="hero-gradient-orb orb-2"></div>
            <div class="hero-gradient-orb orb-3"></div>
            <div class="hero-pattern"></div>
        </div>

        <div class="search-hero-content">
            <div class="hero-badge">
                <span class="badge-icon">🚀</span>
                <span class="badge-text">Over 10,000+ Jobs Available</span>
            </div>

            <div class="hero-text">
                <h1 class="hero-title">
                    <span class="title-gradient">Find Your</span>
                    <br>
                    <span class="title-highlight">Dream Job</span>
                </h1>

            </div>

            <!-- Advanced Search Form -->
            <form method="GET" class="search-form">
                <div class="search-form-container">
                    <div class="search-input-wrapper">
                        <div class="search-input-group enhanced">
                            <div class="input-icon">
                                <svg class="icon-search" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" name="keyword" placeholder="Job title, skills, keywords..."
                                value="<?php echo htmlspecialchars($keyword); ?>" class="search-input enhanced">
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="search-input-wrapper">
                        <div class="search-input-group enhanced">
                            <div class="input-icon">
                                <svg class="icon-location" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" name="location" placeholder="Location, city..."
                                value="<?php echo htmlspecialchars($location); ?>" class="search-input enhanced">
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="search-input-wrapper">
                        <div class="search-input-group enhanced">
                            <div class="input-icon">
                                <svg class="icon-work" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z">
                                    </path>
                                </svg>
                            </div>
                            <select name="employment_type" class="search-input enhanced">
                                <option value="">All Types</option>
                                <option value="Full-time" <?php echo $employment_type === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo $employment_type === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contract" <?php echo $employment_type === 'Contract' ? 'selected' : ''; ?>>
                                    Contract</option>
                                <option value="Internship" <?php echo $employment_type === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="search-button-wrapper">
                        <button type="submit" class="search-btn enhanced">
                            <div class="btn-content">
                                <svg class="btn-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span class="btn-text">Search Jobs</span>
                            </div>
                            <div class="btn-ripple"></div>
                        </button>
                    </div>
                </div>

                <!-- Hidden fields for pagination -->
                <input type="hidden" name="page" value="1">
            </form>

            <div class="quick-search-tags">
                <span class="tags-label">Popular Searches:</span>
                <div class="tags-container">
                    <a href="?keyword=Developer&employment_type=Full-time" class="quick-tag">
                        <span class="tag-icon">💻</span>
                        <span>Developer</span>
                    </a>
                    <a href="?keyword=Designer&work_mode=Remote" class="quick-tag">
                        <span class="tag-icon">🎨</span>
                        <span>Designer</span>
                    </a>
                    <a href="?keyword=Marketing&employment_type=Full-time" class="quick-tag">
                        <span class="tag-icon">📈</span>
                        <span>Marketing</span>
                    </a>
                    <a href="?keyword=Data+Analyst&work_mode=Remote" class="quick-tag">
                        <span class="tag-icon">📊</span>
                        <span>Data Analyst</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="layout-container">



        <!-- Quick Filter Pills -->
        <div class="quick-filters">
            <div class="filter-group">
                <span class="filter-label">Work Mode:</span>
                <div class="filter-pills">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => '', 'page' => 1])); ?>"
                        class="filter-pill <?php echo empty($work_mode) ? 'active' : ''; ?>">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        All
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => 'Remote', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $work_mode === 'Remote' ? 'active' : ''; ?>">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z">
                            </path>
                        </svg>
                        Remote
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => 'Hybrid', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $work_mode === 'Hybrid' ? 'active' : ''; ?>">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4a2 2 0 00-2 2z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Hybrid
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['work_mode' => 'On-site', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $work_mode === 'On-site' ? 'active' : ''; ?>">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        On-site
                    </a>
                </div>
            </div>

            <div class="filter-group">
                <span class="filter-label">Experience:</span>
                <div class="filter-pills">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '', 'page' => 1])); ?>"
                        class="filter-pill <?php echo empty($experience) ? 'active' : ''; ?>">Any</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '0-1 years', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $experience === '0-1 years' ? 'active' : ''; ?>">0-1yr</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '1-3 years', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $experience === '1-3 years' ? 'active' : ''; ?>">1-3yr</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '3-5 years', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $experience === '3-5 years' ? 'active' : ''; ?>">3-5yr</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['experience' => '5+ years', 'page' => 1])); ?>"
                        class="filter-pill <?php echo $experience === '5+ years' ? 'active' : ''; ?>">5+yr</a>
                </div>
            </div>
        </div>

        <!-- Results Area -->
        <div class="results-layout">

            <!-- Left Sidebar Filters -->
            <div class="filters-sidebar">
                <div class="filters-card">
                    <div class="filters-header">
                        <h3 class="filters-title">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Advanced Filters
                        </h3>
                        <a href="?" class="clear-filters">Clear All</a>
                    </div>

                    <form method="GET" class="filters-form">
                        <!-- Preserve existing search parameters -->
                        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                        <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                        <input type="hidden" name="employment_type"
                            value="<?php echo htmlspecialchars($employment_type); ?>">
                        <input type="hidden" name="work_mode" value="<?php echo htmlspecialchars($work_mode); ?>">
                        <input type="hidden" name="experience" value="<?php echo htmlspecialchars($experience); ?>">

                        <!-- Salary Range -->
                        <div class="filter-section">
                            <label class="filter-label">Salary Range
                                (<?php echo htmlspecialchars($currency ?? 'USD'); ?>)</label>
                            <div class="salary-inputs">
                                <input type="number" name="salary_min" placeholder="Min"
                                    value="<?php echo htmlspecialchars($salary_min); ?>" class="filter-input">
                                <span class="salary-separator">-</span>
                                <input type="number" name="salary_max" placeholder="Max"
                                    value="<?php echo htmlspecialchars($_GET['salary_max'] ?? ''); ?>"
                                    class="filter-input">
                            </div>
                        </div>

                        <!-- Posted Within -->
                        <div class="filter-section">
                            <label class="filter-label">Posted Within</label>
                            <select name="posted_within" class="filter-input">
                                <option value="">Any time</option>
                                <option value="24" <?php echo ($_GET['posted_within'] ?? '') === '24' ? 'selected' : ''; ?>>Last 24 hours</option>
                                <option value="168" <?php echo ($_GET['posted_within'] ?? '') === '168' ? 'selected' : ''; ?>>Last 7 days</option>
                                <option value="720" <?php echo ($_GET['posted_within'] ?? '') === '720' ? 'selected' : ''; ?>>Last 30 days</option>
                            </select>
                        </div>

                        <button type="submit" class="apply-filters-btn">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Apply Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Jobs List -->
            <div class="jobs-main">
                <div class="results-header">
                    <div class="results-info">
                        <h2 class="results-title">
                            <?php
                            if (!empty($keyword) || !empty($location)) {
                                echo "Showing " . $totalJobs . " jobs";
                                if (!empty($keyword))
                                    echo " for '" . htmlspecialchars($keyword) . "'";
                                if (!empty($location))
                                    echo " in " . htmlspecialchars($location);
                            } else {
                                echo "All Jobs (" . $totalJobs . ")";
                            }
                            ?>
                        </h2>
                        <p class="results-subtitle">Find your perfect opportunity from our curated listings</p>
                    </div>

                    <div class="sort-dropdown">
                        <label class="sort-label">Sort by:</label>
                        <select
                            onchange="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['sort' => '__SORT__'])); ?>'.replace('__SORT__', this.value)"
                            class="sort-select">
                            <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                            <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance
                            </option>
                            <option value="salary_high" <?php echo $sort === 'salary_high' ? 'selected' : ''; ?>>Salary
                                (High-Low)</option>
                            <option value="salary_low" <?php echo $sort === 'salary_low' ? 'selected' : ''; ?>>Salary
                                (Low-High)</option>
                        </select>
                    </div>
                </div>

                <!-- Jobs List -->
                <?php if (empty($jobs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="empty-title">No jobs found</h3>
                        <p class="empty-description">Try adjusting your filters or search terms to find more opportunities.
                        </p>
                        <a href="?" class="reset-search-btn">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 2a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Reset Search
                        </a>
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($jobs as $job): ?>
                            <div class="modern-job-card">
                                <div class="job-card-header">
                                    <div class="company-logo">
                                        <span
                                            class="company-initials"><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                                    </div>

                                    <div class="job-info">
                                        <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                        <div class="company-info">
                                            <span
                                                class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></span>
                                            <span class="location">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                                <?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="job-meta">
                                        <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
                                            <div class="salary"><?php echo htmlspecialchars($job['salary']); ?></div>
                                        <?php else: ?>
                                            <div class="salary undisclosed">Salary not disclosed</div>
                                        <?php endif; ?>
                                        <div class="posted-date"><?php echo timeAgo($job['created_at']); ?></div>
                                    </div>
                                </div>

                                <div class="job-tags">
                                    <span
                                        class="job-tag employment"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                    <span class="job-tag work-mode"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                                    <span
                                        class="job-tag experience"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                                </div>

                                <div class="job-description">
                                    <?php echo htmlspecialchars(substr(strip_tags($job['description']), 0, 150)); ?>
                                    <?php if (strlen(strip_tags($job['description'])) > 150): ?>...<?php endif; ?>
                                </div>

                                <div class="job-actions">
                                    <?php if (in_array($job['id'], $appliedJobIds)): ?>
                                        <span class="applied-badge">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            Applied
                                        </span>
                                    <?php else: ?>
                                        <a href="<?php echo $CANDIDATE_URL; ?>/apply.php?job_id=<?php echo $job['id']; ?>"
                                            class="apply-btn">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z"
                                                    clip-rule="evenodd"></path>
                                                <path
                                                    d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z">
                                                </path>
                                            </svg>
                                            Apply Now
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?php echo $CANDIDATE_URL; ?>/job-detail.php?id=<?php echo $job['id']; ?>"
                                        class="details-btn">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        View Details
                                    </a>

                                    <button class="save-job-btn" onclick="toggleSaveJob(this, <?php echo $job['id']; ?>)">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Modern Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                <span class="pagination-text">
                                    Showing
                                    <?php echo (($page - 1) * $perPage) + 1; ?>-<?php echo min($page * $perPage, $totalJobs); ?>
                                    of <?php echo $totalJobs; ?> jobs
                                </span>
                            </div>

                            <nav class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                        class="pagination-btn prev">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                if ($startPage > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                                        class="pagination-btn">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="pagination-ellipsis">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                        class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="pagination-ellipsis">...</span>
                                    <?php endif; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"
                                        class="pagination-btn"><?php echo $totalPages; ?></a>
                                <?php endif; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                        class="pagination-btn next">
                                        Next
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                clip-rule="evenodd"></path>
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

<script>
    function toggleSaveJob(button, jobId) {
        // Toggle saved state
        button.classList.toggle('saved');

        // Here you would typically make an AJAX call to save/unsave the job
        // For now, we'll just toggle the visual state
        if (button.classList.contains('saved')) {
            // Show success message
            showNotification('Job saved successfully!');
        } else {
            showNotification('Job removed from saved items');
        }
    }

    function showNotification(message) {
        // Create a simple notification
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Add animations
    const style = document.createElement('style');
    style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
    document.head.appendChild(style);
</script>

<?php require_once '../includes/footer.php'; ?>