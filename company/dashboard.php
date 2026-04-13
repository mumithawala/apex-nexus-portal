<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Dashboard - Apex Nexus";
require_once '../includes/header.php';

// Get current company record with user data
$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.email as user_email 
    FROM companies c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.user_id = ? AND c.is_deleted = 0 AND u.is_deleted = 0
");
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company['id'] ?? null;

// Time-based greeting
$hour = date('H');
$greeting = '';
$greetingEmoji = '';

if ($hour >= 5 && $hour < 12) {
  $greeting = 'Good morning';
  $greetingEmoji = 'sunrise';
} elseif ($hour >= 12 && $hour < 17) {
  $greeting = 'Good afternoon';
  $greetingEmoji = 'sunny';
} elseif ($hour >= 17 && $hour < 21) {
  $greeting = 'Good evening';
  $greetingEmoji = 'moon';
} else {
  $greeting = 'Working late';
  $greetingEmoji = 'moon';
}

$dayOfWeek = date('l');
$dayMessages = [
  'Monday' => 'New week, new opportunities!',
  'Tuesday' => 'Keep momentum going!',
  'Wednesday' => 'Halfway to success!',
  'Thursday' => 'Almost there, stay focused!',
  'Friday' => 'Finish strong and prepare for success!',
  'Saturday' => 'Weekend planning for growth!',
  'Sunday' => 'Rest and recharge for week ahead!'
];

$dayMessage = $dayMessages[$dayOfWeek] ?? '';

// Dashboard queries
try {
  // 1. Total active jobs
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND status = 'active' AND is_deleted = 0");
  $stmt->execute([$companyId]);
  $activeJobs = $stmt->fetchColumn();

  // 2. Total applications
  $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.company_id = ? AND a.is_deleted = 0 AND j.is_deleted = 0
    ");
  $stmt->execute([$companyId]);
  $totalApplications = $stmt->fetchColumn();

  // 3. Applications by status
  $stmt = $pdo->prepare("
        SELECT a.status, COUNT(*) as count FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.company_id = ? AND a.is_deleted = 0 AND j.is_deleted = 0
        GROUP BY a.status
    ");
  $stmt->execute([$companyId]);
  $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

  $applied = $statusCounts['applied'] ?? 0;
  $reviewed = $statusCounts['reviewed'] ?? 0;
  $shortlisted = $statusCounts['shortlisted'] ?? 0;
  $rejected = $statusCounts['rejected'] ?? 0;

  // 4. 5 most recent applications
  $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, c.full_name, c.email, c.total_experience
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN candidates c ON a.candidate_id = c.id
        WHERE j.company_id = ? AND a.is_deleted = 0 AND j.is_deleted = 0
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
  $stmt->execute([$companyId]);
  $recentApplications = $stmt->fetchAll();

  // 5. 3 most recent job postings
  $stmt = $pdo->prepare("
        SELECT j.*, COUNT(a.id) as application_count
        FROM jobs j 
        LEFT JOIN applications a ON j.id = a.job_id AND a.is_deleted = 0
        WHERE j.company_id = ? AND j.is_deleted = 0
        ORDER BY j.created_at DESC 
        LIMIT 3
    ");
  $stmt->execute([$companyId]);
  $recentJobs = $stmt->fetchAll();

  // 6. Jobs expiring within 7 days
  $stmt = $pdo->prepare("
        SELECT id, title, deadline FROM jobs 
        WHERE company_id = ? AND is_deleted = 0 
        AND deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY deadline ASC
    ");
  $stmt->execute([$companyId]);
  $expiringJobs = $stmt->fetchAll();

} catch (PDOException $e) {
  error_log("Dashboard query error: " . $e->getMessage());
  $activeJobs = $totalApplications = $applied = $reviewed = $shortlisted = $rejected = 0;
  $recentApplications = $recentJobs = $expiringJobs = [];
}
?>

<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/company-nav.css">
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/company-modern.css">

<!-- Modern Company Navigation -->
<?php include '../includes/company-navbar.php'; ?>

<!-- Main Content Area -->
<div class="company-layout">
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
                  <path
                    d="M12 2v2m0 16v2m4.22-13.22l1.42 1.42M6.36 18.36l1.42-1.42M2 12h2m16 0h2M6.36 5.64l1.42-1.42M17.64 18.36l-1.42-1.42" />
                  <circle cx="12" cy="12" r="5" />
                </svg>
              <?php elseif ($greetingEmoji === 'sunny'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="5" />
                  <path
                    d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 1.54l4.24 4.24M1 12h6m6 0h6m-13.22 4.22l4.24 4.24M17.46 17.46l4.24 4.24" />
                </svg>
              <?php elseif ($greetingEmoji === 'moon'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon
                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                </svg>
              <?php endif; ?>
            </div>
            <h1 class="welcome-title">
              <span class="title-gradient"><?php echo $greeting; ?>,</span>
              <br>
              <span class="title-highlight"><?php echo htmlspecialchars($company['company_name']); ?>!</span>
            </h1>
            <p class="welcome-subtitle"><?php echo "Ready to find amazing talent for your team?"; ?></p>
            <?php if (!empty($dayMessage)): ?>
              <p class="day-message"><?php echo $dayMessage; ?></p>
            <?php endif; ?>
          </div>

          <div class="welcome-visual">
            <div class="career-icons">
              <div class="icon-item floating">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                <span>Jobs</span>
              </div>
              <div class="icon-item floating" style="animation-delay: 0.2s;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                <span>Applicants</span>
              </div>
              <div class="icon-item floating" style="animation-delay: 0.4s;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                  <circle cx="8.5" cy="8.5" r="1.5" />
                  <polyline points="21 15 16 10 5 21" />
                </svg>
                <span>Analytics</span>
              </div>
              <div class="icon-item floating" style="animation-delay: 0.6s;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
                <span>Success</span>
              </div>
            </div>
            <div class="stats-preview">
              <div class="mini-stat">
                <div class="mini-number"><?php echo $activeJobs; ?></div>
                <div class="mini-label">Active Jobs</div>
              </div>
              <div class="mini-stat">
                <div class="mini-number"><?php echo $totalApplications; ?></div>
                <div class="mini-label">Applications</div>
              </div>
              <div class="mini-stat">
                <div class="mini-number"><?php echo $shortlisted; ?></div>
                <div class="mini-label">Shortlisted</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Overview -->
    <section class="stats-overview">
      <div class="stats-header">
        <div class="stats-title">
          <h2>Dashboard Overview</h2>
          <p>Real-time insights into your recruitment performance</p>
        </div>
        <div class="stats-period">
          <select class="period-selector">
            <option value="today">Today</option>
            <option value="week" selected>This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
          </select>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-card-inner">
            <div class="stat-header">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                  <line x1="9" y1="9" x2="15" y2="9" />
                  <line x1="9" y1="15" x2="15" y2="15" />
                </svg>
              </div>
              <div class="stat-menu">
                <button class="stat-menu-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1" />
                    <circle cx="12" cy="5" r="1" />
                    <circle cx="12" cy="19" r="1" />
                  </svg>
                </button>
              </div>
            </div>
            <div class="stat-body">
              <div class="stat-number-wrapper">
                <div class="stat-number"><?php echo $activeJobs; ?></div>
                <div class="stat-subtitle">Active Jobs</div>
              </div>
              <div class="stat-chart">
                <div class="mini-chart">
                  <div class="chart-bar" style="height: 40%"></div>
                  <div class="chart-bar" style="height: 60%"></div>
                  <div class="chart-bar" style="height: 80%"></div>
                  <div class="chart-bar" style="height: 65%"></div>
                  <div class="chart-bar" style="height: 90%"></div>
                  <div class="chart-bar" style="height: 100%"></div>
                  <div class="chart-bar" style="height: 85%"></div>
                </div>
              </div>
            </div>
            <div class="stat-footer">
              <div class="stat-trend positive">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>+12%</span>
                <small>vs last week</small>
              </div>
            </div>
          </div>
        </div>

        <div class="stat-card success">
          <div class="stat-card-inner">
            <div class="stat-header">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                  <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
              </div>
              <div class="stat-menu">
                <button class="stat-menu-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1" />
                    <circle cx="12" cy="5" r="1" />
                    <circle cx="12" cy="19" r="1" />
                  </svg>
                </button>
              </div>
            </div>
            <div class="stat-body">
              <div class="stat-number-wrapper">
                <div class="stat-number"><?php echo $totalApplications; ?></div>
                <div class="stat-subtitle">Total Applications</div>
              </div>
              <div class="stat-chart">
                <div class="mini-chart">
                  <div class="chart-bar" style="height: 70%"></div>
                  <div class="chart-bar" style="height: 85%"></div>
                  <div class="chart-bar" style="height: 60%"></div>
                  <div class="chart-bar" style="height: 90%"></div>
                  <div class="chart-bar" style="height: 75%"></div>
                  <div class="chart-bar" style="height: 95%"></div>
                  <div class="chart-bar" style="height: 88%"></div>
                </div>
              </div>
            </div>
            <div class="stat-footer">
              <div class="stat-trend positive">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>+8%</span>
                <small>vs last week</small>
              </div>
            </div>
          </div>
        </div>

        <div class="stat-card warning">
          <div class="stat-card-inner">
            <div class="stat-header">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12 6 12 12 16 14" />
                </svg>
              </div>
              <div class="stat-menu">
                <button class="stat-menu-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1" />
                    <circle cx="12" cy="5" r="1" />
                    <circle cx="12" cy="19" r="1" />
                  </svg>
                </button>
              </div>
            </div>
            <div class="stat-body">
              <div class="stat-number-wrapper">
                <div class="stat-number"><?php echo $reviewed; ?></div>
                <div class="stat-subtitle">Under Review</div>
              </div>
              <div class="stat-chart">
                <div class="mini-chart">
                  <div class="chart-bar" style="height: 50%"></div>
                  <div class="chart-bar" style="height: 50%"></div>
                  <div class="chart-bar" style="height: 50%"></div>
                  <div class="chart-bar" style="height: 50%"></div>
                  <div class="chart-bar" style="height: 50%"></div>
                  <div class="chart-bar" style="height: 50%"></div>
                  <div class="chart-bar" style="height: 50%"></div>
                </div>
              </div>
            </div>
            <div class="stat-footer">
              <div class="stat-trend neutral">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>0%</span>
                <small>vs last week</small>
              </div>
            </div>
          </div>
        </div>

        <div class="stat-card info">
          <div class="stat-card-inner">
            <div class="stat-header">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </div>
              <div class="stat-menu">
                <button class="stat-menu-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1" />
                    <circle cx="12" cy="5" r="1" />
                    <circle cx="12" cy="19" r="1" />
                  </svg>
                </button>
              </div>
            </div>
            <div class="stat-body">
              <div class="stat-number-wrapper">
                <div class="stat-number"><?php echo $shortlisted; ?></div>
                <div class="stat-subtitle">Shortlisted</div>
              </div>
              <div class="stat-chart">
                <div class="mini-chart">
                  <div class="chart-bar" style="height: 30%"></div>
                  <div class="chart-bar" style="height: 45%"></div>
                  <div class="chart-bar" style="height: 60%"></div>
                  <div class="chart-bar" style="height: 80%"></div>
                  <div class="chart-bar" style="height: 70%"></div>
                  <div class="chart-bar" style="height: 85%"></div>
                  <div class="chart-bar" style="height: 92%"></div>
                </div>
              </div>
            </div>
            <div class="stat-footer">
              <div class="stat-trend positive">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>+24%</span>
                <small>vs last week</small>
              </div>
            </div>
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
            <p class="section-subtitle">Track your latest applicant submissions</p>
          </div>
          <a href="<?php echo $COMPANY_URL; ?>/applicants.php" class="view-all-btn">View All</a>
        </div>

        <?php if (count($recentApplications) > 0): ?>
          <div class="applications-list">
            <?php foreach ($recentApplications as $app): ?>
              <div class="application-card">
                <div class="app-header">
                  <div class="candidate-info">
                    <div class="candidate-avatar">
                      <?php echo substr(htmlspecialchars($app['full_name']), 0, 2); ?>
                    </div>
                    <div class="candidate-details">
                      <h4 class="candidate-name"><?php echo htmlspecialchars($app['full_name']); ?></h4>
                      <p class="candidate-email"><?php echo htmlspecialchars($app['email']); ?></p>
                    </div>
                  </div>
                  <div class="app-status">
                    <span class="status-badge status-<?php echo $app['status']; ?>">
                      <?php echo ucfirst($app['status']); ?>
                    </span>
                  </div>
                </div>
                <div class="app-meta">
                  <div class="job-info">
                    <span class="job-title"><?php echo htmlspecialchars($app['job_title']); ?></span>
                    <span class="experience"><?php echo $app['total_experience'] ?? '0'; ?> yrs exp</span>
                  </div>
                  <div class="app-actions">
                    <span class="app-date">Applied <?php echo timeAgo($app['created_at']); ?></span>
                    <a href="<?php echo $COMPANY_URL; ?>/applicant-detail.php?id=<?php echo $app['id']; ?>"
                      class="view-btn">View Details</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
            </div>
            <h3>No applications yet</h3>
            <p>Post a job to start receiving applications</p>
            <a href="<?php echo $COMPANY_URL; ?>/post-job.php" class="action-btn primary">Post a Job</a>
          </div>
        <?php endif; ?>
      </section>

      <!-- Active Jobs & Exiring Soon -->
      <section class="jobs-section">
        <div class="section-header">
          <div class="section-title">
            <h2>Active Jobs</h2>
            <p class="section-subtitle">Manage your current job postings</p>
          </div>
          <a href="<?php echo $COMPANY_URL; ?>/manage-jobs.php" class="view-all-btn">Manage All</a>
        </div>

        <?php if (count($recentJobs) > 0): ?>
          <div class="jobs-list">
            <?php foreach ($recentJobs as $job): ?>
              <div class="job-card">
                <div class="job-header">
                  <div class="job-info">
                    <h4 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h4>
                    <p class="posted-date">Posted <?php echo timeAgo($job['created_at']); ?></p>
                  </div>
                  <div class="job-status">
                    <span class="status-badge status-<?php echo $job['status']; ?>">
                      <?php echo ucfirst($job['status']); ?>
                    </span>
                  </div>
                </div>
                <div class="job-meta">
                  <div class="applications-count">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                      <path d="M23 21v-2a4 4 0 0 0-4-4h-1v-4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v4h-1" />
                    </svg>
                    <?php echo $job['application_count']; ?> Applications
                  </div>
                  <a href="<?php echo $COMPANY_URL; ?>/edit-job.php?id=<?php echo $job['id']; ?>" class="edit-btn">Edit</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                <line x1="9" y1="9" x2="15" y2="9" />
                <line x1="9" y1="15" x2="15" y2="15" />
              </svg>
            </div>
            <h3>No jobs posted yet</h3>
            <p>Start by posting your first job opening</p>
            <a href="<?php echo $COMPANY_URL; ?>/post-job.php" class="action-btn primary">Post a Job</a>
          </div>
        <?php endif; ?>

        <?php if (count($expiringJobs) > 0): ?>
          <div class="expiring-jobs">
            <h3>Jobs Expiring Soon</h3>
            <div class="expiring-list">
              <?php foreach ($expiringJobs as $job): ?>
                <div class="expiring-job">
                  <div class="expiring-info">
                    <span class="job-title"><?php echo htmlspecialchars($job['title']); ?></span>
                    <span class="deadline">Expires: <?php echo formatDate($job['deadline']); ?></span>
                  </div>
                  <a href="<?php echo $COMPANY_URL; ?>/edit-job.php?id=<?php echo $job['id']; ?>"
                    class="extend-btn">Extend</a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <!-- Hiring Pipeline -->
    <section class="pipeline-section">
      <div class="section-header">
        <div class="section-title">
          <h2>Hiring Pipeline</h2>
          <p class="section-subtitle">Overview of your recruitment process</p>
        </div>
      </div>
      <div class="pipeline-grid">
        <div class="pipeline-stage applied">
          <div class="stage-header">
            <div class="stage-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
            </div>
            <span class="stage-title">Applied</span>
          </div>
          <div class="stage-count"><?php echo $applied; ?></div>
          <div class="pipeline-bar">
            <div class="pipeline-fill" style="width: <?php echo round(($applied / max($applied, 1)) * 100); ?>%"></div>
          </div>
        </div>

        <div class="pipeline-stage reviewed">
          <div class="stage-header">
            <div class="stage-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
            </div>
            <span class="stage-title">Reviewed</span>
          </div>
          <div class="stage-count"><?php echo $reviewed; ?></div>
          <div class="pipeline-bar">
            <div class="pipeline-fill" style="width: <?php echo round(($reviewed / max($reviewed, 1)) * 100); ?>%"></div>
          </div>
        </div>

        <div class="pipeline-stage shortlisted">
          <div class="stage-header">
            <div class="stage-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
            </div>
            <span class="stage-title">Shortlisted</span>
          </div>
          <div class="stage-count"><?php echo $shortlisted; ?></div>
          <div class="pipeline-bar">
            <div class="pipeline-fill" style="width: <?php echo round(($shortlisted / max($shortlisted, 1)) * 100); ?>%">
            </div>
          </div>
        </div>

        <div class="pipeline-stage rejected">
          <div class="stage-header">
            <div class="stage-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </div>
            <span class="stage-title">Rejected</span>
          </div>
          <div class="stage-count"><?php echo $rejected; ?></div>
          <div class="pipeline-bar">
            <div class="pipeline-fill" style="width: <?php echo round(($rejected / max($rejected, 1)) * 100); ?>%"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Quick Actions Floating Button -->
<div class="quick-actions">
  <button class="fab" onclick="toggleQuickActions()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="12" y1="5" x2="12" y2="19" />
      <line x1="5" y1="12" x2="19" y2="12" />
    </svg>
  </button>

  <div id="quickActionsMenu" class="quick-actions-menu hidden">
    <a href="<?php echo $COMPANY_URL; ?>/post-job.php" class="quick-action">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 4v16m8-8H4" />
      </svg>
      <span>Post Job</span>
    </a>
    <a href="<?php echo $COMPANY_URL; ?>/manage-jobs.php" class="quick-action">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
        <line x1="9" y1="9" x2="15" y2="9" />
        <line x1="9" y1="15" x2="15" y2="15" />
      </svg>
      <span>Manage Jobs</span>
    </a>
    <a href="<?php echo $COMPANY_URL; ?>/applicants.php" class="quick-action">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M23 21v-2a4 4 0 0 0-4-4h-1v-4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v4h-1" />
      </svg>
      <span>View Applicants</span>
    </a>
    <a href="<?php echo $COMPANY_URL; ?>/profile.php" class="quick-action">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
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