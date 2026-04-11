<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
require_once '../includes/urls.php';
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
$hour = date('H');
$dayOfWeek = date('l');

// Dynamic greeting based on time and day
if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning";
    $greetingEmoji = "sunrise";
    $greetingMessage = "Ready to conquer today's opportunities?";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "Good Afternoon";
    $greetingEmoji = "sunny";
    $greetingMessage = "Perfect time to advance your career!";
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = "Good Evening";
    $greetingEmoji = "moon";
    $greetingMessage = "Wind down and plan your next move!";
} else {
    $greeting = "Hello Night Owl";
    $greetingEmoji = "stars";
    $greetingMessage = "Late night planning for success!";
}

// Add day-specific messages
$dayMessages = [
    'Monday' => 'New week, new opportunities!',
    'Tuesday' => 'Keep the momentum going!',
    'Wednesday' => 'Halfway to success!',
    'Thursday' => 'Almost there, stay focused!',
    'Friday' => 'Finish strong and prepare for success!',
    'Saturday' => 'Weekend research for career growth!',
    'Sunday' => 'Rest and recharge for the week ahead!'
];

$dayMessage = $dayMessages[$dayOfWeek] ?? '';

?>

<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate-nav.css">
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
        
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-content">
                <div class="hero-pattern"></div>
                <div class="hero-orb-1"></div>
                <div class="hero-orb-2"></div>
                <div class="welcome-content-inner">
                    <div class="welcome-text">
                        <div class="greeting-emoji">
                            <?php if ($greetingEmoji === 'sunrise'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v2m0 16v2m4.22-13.22l1.42 1.42M6.36 18.36l1.42-1.42M2 12h2m16 0h2M6.36 5.64l1.42-1.42M17.64 18.36l-1.42-1.42"/>
                                    <circle cx="12" cy="12" r="5"/>
                                </svg>
                            <?php elseif ($greetingEmoji === 'sunny'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="5"/>
                                    <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 1.54l4.24 4.24M1 12h6m6 0h6m-13.22 4.22l4.24 4.24M17.46 17.46l4.24 4.24"/>
                                </svg>
                            <?php elseif ($greetingEmoji === 'moon'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <h1 class="welcome-title">
                            <span class="title-gradient"><?php echo $greeting; ?>,</span>
                            <br>
                            <span class="title-highlight"><?php echo htmlspecialchars($candidate['first_name'] ?? 'User'); ?>!</span>
                        </h1>
                        <p class="welcome-subtitle"><?php echo $greetingMessage; ?></p>
                        <?php if (!empty($dayMessage)): ?>
                            <p class="day-message"><?php echo $dayMessage; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="welcome-visual">
                        <div class="career-icons">
                            <div class="icon-item floating">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <span>Applications</span>
                            </div>
                            <div class="icon-item floating" style="animation-delay: 0.2s;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span>Profile</span>
                            </div>
                            <div class="icon-item floating" style="animation-delay: 0.4s;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                                <span>Jobs</span>
                            </div>
                            <div class="icon-item floating" style="animation-delay: 0.6s;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                </svg>
                                <span>Success</span>
                            </div>
                        </div>
                        <div class="stats-preview">
                            <div class="mini-stat">
                                <div class="mini-number"><?php echo $totalApplications; ?></div>
                                <div class="mini-label">Applied</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-number"><?php echo $statusCounts['shortlisted']; ?></div>
                                <div class="mini-label">Shortlisted</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-number"><?php echo $completion; ?>%</div>
                                <div class="mini-label">Profile</div>
                            </div>
                        </div>
                    </div>
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
                        <a href="<?php echo $CANDIDATE_URL; ?>/profile.php" class="boost-btn">Complete Now</a>
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
                    <a href="<?php echo $CANDIDATE_URL; ?>/my-applications.php" class="view-all-btn">View All</a>
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
                        <a href="<?php echo $CANDIDATE_URL; ?>/search-jobs.php" class="action-btn primary">Find Jobs</a>
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
                                    <a href="<?php echo $CANDIDATE_URL; ?>/job-detail.php?id=<?php echo $app['job_id']; ?>" class="view-btn">View Details</a>
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
                    <a href="<?php echo $CANDIDATE_URL; ?>/search-jobs.php" class="view-all-btn">Browse All</a>
                </div>
                
                <div class="jobs-grid">
                    <?php foreach ($recommendedJobs as $job): ?>
                        <div class="modern-job-card">
                            <div class="job-card-header">
                                <div class="company-logo">
                                    <span class="company-initials"><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                                </div>

                                <div class="job-info">
                                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <div class="company-info">
                                        <span class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></span>
                                        <span class="location">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
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
                                <span class="job-tag employment"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                <span class="job-tag work-mode"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                                <span class="job-tag experience"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                            </div>

                            <div class="job-description">
                                <?php echo htmlspecialchars(substr(strip_tags($job['description']), 0, 150)); ?>
                                <?php if (strlen(strip_tags($job['description'])) > 150): ?>...<?php endif; ?>
                            </div>

                            <div class="job-actions">
                                <a href="<?php echo $CANDIDATE_URL; ?>/apply.php?job_id=<?php echo $job['id']; ?>" class="apply-btn">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"></path>
                                    </svg>
                                    Apply Now
                                </a>

                                <a href="<?php echo $CANDIDATE_URL; ?>/job-detail.php?id=<?php echo $job['id']; ?>" class="details-btn">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
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
        <a href="<?php echo $CANDIDATE_URL; ?>/search-jobs.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <span>Search Jobs</span>
        </a>
        <a href="<?php echo $CANDIDATE_URL; ?>/upload-resume.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <span>Upload Resume</span>
        </a>
        <a href="<?php echo $CANDIDATE_URL; ?>/profile.php" class="quick-action">
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