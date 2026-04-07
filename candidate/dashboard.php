<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
requireRole('candidate');
$pageTitle = "Dashboard - Apex Nexus";
require_once '../includes/header.php';

// Get current candidate record with user data
$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.email as user_email 
    FROM candidates c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.user_id = ? AND c.is_deleted = 0 AND u.is_deleted = 0
");
$stmt->execute([$userId]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);
$candidateId = $candidate['id'] ?? null;

// Get dashboard statistics
$totalApplications = 0;
$statusCounts = ['applied' => 0, 'reviewed' => 0, 'shortlisted' => 0, 'rejected' => 0];
$recentApplications = [];
$recommendedJobs = [];

if ($candidateId) {
    // Total applications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE candidate_id = ? AND is_deleted = 0");
    $stmt->execute([$candidateId]);
    $totalApplications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Applications by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM applications WHERE candidate_id = ? AND is_deleted = 0 GROUP BY status");
    $stmt->execute([$candidateId]);
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusData as $row) {
        $statusCounts[$row['status']] = $row['count'];
    }
    
    // Recent applications
    $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, c.company_name 
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        WHERE a.candidate_id = ? AND a.is_deleted = 0
        ORDER BY a.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$candidateId]);
    $recentApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recommended jobs (latest active jobs)
    $stmt = $pdo->prepare("
        SELECT j.*, c.company_name, c.city as company_city, c.state as company_state
        FROM jobs j
        JOIN companies c ON j.company_id = c.id
        WHERE j.status = 'active' AND j.is_deleted = 0 
        AND (j.deadline IS NULL OR j.deadline >= CURDATE())
        ORDER BY j.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $recommendedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$completion = calculateProfileCompletion($candidate);
$greeting = "Good morning";
$hour = date('H');
if ($hour >= 12 && $hour < 17) $greeting = "Good afternoon";
elseif ($hour >= 17) $greeting = "Good evening";
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
        
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h1 class="welcome-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($candidate['first_name'] ?? 'User'); ?>!</h1>
                    <p class="welcome-subtitle">Track your job search and career progress</p>
                </div>
                <?php if ($completion < 80): ?>
                    <div class="profile-boost">
                        <div class="boost-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                        </div>
                        <div class="boost-content">
                            <p class="boost-text">Complete your profile to get <strong>3x more interviews</strong></p>
                            <div class="boost-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $completion; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo $completion; ?>%</span>
                            </div>
                        </div>
                        <a href="/apex-nexus-portal/candidate/profile.php" class="boost-btn">Complete Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Stats Overview -->
        <section class="stats-overview">
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="9" x2="15" y2="9"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totalApplications; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-trend positive">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        </svg>
                        <span>+12%</span>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $statusCounts['shortlisted']; ?></div>
                        <div class="stat-label">Shortlisted</div>
                    </div>
                    <div class="stat-trend positive">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        </svg>
                        <span>+8%</span>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $statusCounts['reviewed']; ?></div>
                        <div class="stat-label">Under Review</div>
                    </div>
                    <div class="stat-trend neutral">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>0%</span>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">247</div>
                        <div class="stat-label">Profile Views</div>
                    </div>
                    <div class="stat-trend positive">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        </svg>
                        <span>+24%</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="content-grid">
            
            <!-- Recent Applications -->
            <section class="applications-section">
                <div class="section-header">
                    <div class="section-title">
                        <h2>Recent Applications</h2>
                        <p class="section-subtitle">Track your latest job applications</p>
                    </div>
                    <a href="/apex-nexus-portal/candidate/my-applications.php" class="view-all-btn">View All</a>
                </div>
                
                <?php if (empty($recentApplications)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <h3>No applications yet</h3>
                        <p>Start applying for jobs to see them here</p>
                        <a href="/apex-nexus-portal/candidate/search-jobs.php" class="action-btn primary">Find Jobs</a>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($recentApplications as $app): ?>
                            <div class="application-card">
                                <div class="app-header">
                                    <div class="company-info">
                                        <div class="company-avatar">
                                            <?php echo substr(htmlspecialchars($app['company_name']), 0, 2); ?>
                                        </div>
                                        <div class="company-details">
                                            <h4 class="job-title"><?php echo htmlspecialchars($app['job_title']); ?></h4>
                                            <p class="company-name"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="app-status">
                                        <span class="status-badge status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="app-meta">
                                    <span class="app-date">Applied <?php echo timeAgo($app['created_at']); ?></span>
                                    <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $app['job_id']; ?>" class="view-btn">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recommended Jobs -->
            <section class="jobs-section">
                <div class="section-header">
                    <div class="section-title">
                        <h2>Recommended for You</h2>
                        <p class="section-subtitle">Jobs matching your profile</p>
                    </div>
                    <a href="/apex-nexus-portal/candidate/search-jobs.php" class="view-all-btn">Browse All</a>
                </div>
                
                <div class="jobs-grid">
                    <?php foreach ($recommendedJobs as $job): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div class="company-logo">
                                    <?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?>
                                </div>
                                <div class="job-info">
                                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    <p class="location"><?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?></p>
                                </div>
                            </div>
                            
                            <div class="job-tags">
                                <span class="tag"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                <span class="tag"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                                <span class="tag"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                            </div>
                            
                            <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
                                <div class="job-salary">
                                    <span class="salary-amount"><?php echo htmlspecialchars($job['salary']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="job-footer">
                                <span class="posted-date">Posted <?php echo timeAgo($job['created_at']); ?></span>
                                <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $job['id']; ?>" class="apply-btn">Apply Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

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
        <a href="/apex-nexus-portal/candidate/search-jobs.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <span>Search Jobs</span>
        </a>
        <a href="/apex-nexus-portal/candidate/upload-resume.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <span>Upload Resume</span>
        </a>
        <a href="/apex-nexus-portal/candidate/profile.php" class="quick-action">
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