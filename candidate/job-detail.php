<?php
require_once '../includes/auth.php';
requireRole('candidate');
$pageTitle = "Job Details - Apex Nexus";
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

// Get job details
$jobId = $_GET['id'] ?? '';
if (empty($jobId) || !is_numeric($jobId)) {
    setFlash('error', 'Invalid job ID');
    redirect('/apex-nexus-portal/candidate/search-jobs.php');
}

// Fetch job with company details
$stmt = $pdo->prepare("
    SELECT j.*, c.company_name, c.city as company_city, c.state as company_state, 
           c.website as company_website, c.logo as company_logo
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    WHERE j.id = ? AND j.status = 'active' AND j.is_deleted = 0
");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    setFlash('error', 'Job not found');
    redirect('/apex-nexus-portal/candidate/search-jobs.php');
}

// Check if candidate already applied
$alreadyApplied = false;
if ($candidateId) {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND candidate_id = ? AND is_deleted = 0");
    $stmt->execute([$jobId, $candidateId]);
    $alreadyApplied = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

// Get similar jobs (same employment_type or work_mode, limit 3)
$stmt = $pdo->prepare("
    SELECT j.*, c.company_name, c.city as company_city, c.state as company_state
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    WHERE j.id != ? AND j.status = 'active' AND j.is_deleted = 0 
    AND (j.deadline IS NULL OR j.deadline >= CURDATE())
    AND (j.employment_type = ? OR j.work_mode = ? OR j.company_id = ?)
    ORDER BY j.created_at DESC
    LIMIT 3
");
$stmt->execute([$jobId, $job['employment_type'], $job['work_mode'], $job['company_id']]);
$similarJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
        
        <!-- Job Information Card -->
        <div class="apply-job-card">
            <div class="apply-job-header">
                <div class="apply-company-logo">
                    <span><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                </div>
                <div class="apply-job-details">
                    <h1 class="apply-job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <p class="apply-company-name"><?php echo htmlspecialchars($job['company_name']); ?> · <?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?></p>
                    <div class="apply-job-meta">
                        <span class="apply-meta-item"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                        <span class="apply-meta-item"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                        <span class="apply-meta-item"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                        <?php if (!empty($job['deadline'])): ?>
                            <span class="apply-meta-item deadline">Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Action Button -->
            <div class="apply-job-actions">
                <?php if ($alreadyApplied): ?>
                    <div class="apply-applied-badge">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Already Applied
                    </div>
                <?php else: ?>
                    <a href="/apex-nexus-portal/candidate/apply.php?job_id=<?php echo $job['id']; ?>" 
                       class="apply-apply-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Apply Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Salary Information -->
        <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
            <div class="apply-salary-card">
                <div class="apply-salary-label">Salary Range</div>
                <div class="apply-salary-amount"><?php echo htmlspecialchars($job['salary']); ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Posted Date -->
        <div class="apply-posted-info">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Posted <?php echo timeAgo($job['created_at']); ?>
        </div>

        <!-- Main Content Grid -->
        <div class="apply-detail-grid">
            
            <!-- Job Details Section -->
            <div class="apply-detail-main">
                <div class="apply-detail-card">
                    <!-- Modern Tabs -->
                    <div class="apply-tabs">
                        <button onclick="showTab('overview')" id="tab-overview" 
                                class="apply-tab active">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Overview
                        </button>
                        <button onclick="showTab('requirements')" id="tab-requirements" 
                                class="apply-tab">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Requirements
                        </button>
                        <button onclick="showTab('responsibilities')" id="tab-responsibilities" 
                                class="apply-tab">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A8.937 8.937 0 0112 21a8.937 8.937 0 01-9-8.745M21 12a9 9 0 01-9 9m0-9a9 9 0 00-9 9m9 9v-6m0 0V3m0 6h-6m6 0h6"/>
                            </svg>
                            Responsibilities
                        </button>
                        <button onclick="showTab('benefits')" id="tab-benefits" 
                                class="apply-tab">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                            </svg>
                            Benefits
                        </button>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="apply-tab-content">
                        <!-- Overview Tab -->
                        <div id="content-overview" class="apply-tab-panel">
                            <h3 class="apply-tab-title">Job Overview</h3>
                            <div class="apply-tab-text">
                                <?php echo !empty($job['description']) ? nl2br(htmlspecialchars($job['description'])) : '<p class="text-gray-500">No description available.</p>'; ?>
                            </div>
                        </div>
                        
                        <!-- Requirements Tab -->
                        <div id="content-requirements" class="apply-tab-panel hidden">
                            <h3 class="apply-tab-title">Requirements</h3>
                            <div class="apply-tab-text">
                                <?php echo !empty($job['requirements']) ? nl2br(htmlspecialchars($job['requirements'])) : '<p class="text-gray-500">No specific requirements listed.</p>'; ?>
                            </div>
                        </div>
                        
                        <!-- Responsibilities Tab -->
                        <div id="content-responsibilities" class="apply-tab-panel hidden">
                            <h3 class="apply-tab-title">Responsibilities</h3>
                            <div class="apply-tab-text">
                                <?php echo !empty($job['responsibilities']) ? nl2br(htmlspecialchars($job['responsibilities'])) : '<p class="text-gray-500">No responsibilities listed.</p>'; ?>
                            </div>
                        </div>
                        
                        <!-- Benefits Tab -->
                        <div id="content-benefits" class="apply-tab-panel hidden">
                            <h3 class="apply-tab-title">Perks & Benefits</h3>
                            <div class="apply-tab-text">
                                <?php echo !empty($job['perks']) ? nl2br(htmlspecialchars($job['perks'])) : '<p class="text-gray-500">No perks and benefits listed.</p>'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="apply-detail-sidebar">
                
                <!-- Company Information -->
                <div class="apply-sidebar-card">
                    <h3 class="apply-sidebar-title">Company Information</h3>
                    <div class="apply-sidebar-content">
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Company</div>
                            <div class="apply-sidebar-value"><?php echo htmlspecialchars($job['company_name']); ?></div>
                        </div>
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Location</div>
                            <div class="apply-sidebar-value"><?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?></div>
                        </div>
                        <?php if (!empty($job['company_website'])): ?>
                            <div class="apply-sidebar-item">
                                <div class="apply-sidebar-label">Website</div>
                                <a href="<?php echo htmlspecialchars($job['company_website']); ?>" 
                                   target="_blank" class="apply-sidebar-link">
                                    <?php echo htmlspecialchars($job['company_website']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Job Summary -->
                <div class="apply-sidebar-card">
                    <h3 class="apply-sidebar-title">Job Summary</h3>
                    <div class="apply-sidebar-content">
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Posted</div>
                            <div class="apply-sidebar-value"><?php echo timeAgo($job['created_at']); ?></div>
                        </div>
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Deadline</div>
                            <div class="apply-sidebar-value">
                                <?php echo !empty($job['deadline']) ? date('M j, Y', strtotime($job['deadline'])) : 'Open'; ?>
                            </div>
                        </div>
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Experience</div>
                            <div class="apply-sidebar-value"><?php echo htmlspecialchars($job['experience_required']); ?></div>
                        </div>
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Type</div>
                            <div class="apply-sidebar-value"><?php echo htmlspecialchars($job['employment_type']); ?></div>
                        </div>
                        <div class="apply-sidebar-item">
                            <div class="apply-sidebar-label">Work Mode</div>
                            <div class="apply-sidebar-value"><?php echo htmlspecialchars($job['work_mode']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Similar Jobs -->
                <?php if (!empty($similarJobs)): ?>
                    <div class="apply-sidebar-card">
                        <h3 class="apply-sidebar-title">Similar Jobs</h3>
                        <div class="apply-similar-jobs">
                            <?php foreach ($similarJobs as $similarJob): ?>
                                <div class="apply-similar-job">
                                    <h4 class="apply-similar-title"><?php echo htmlspecialchars($similarJob['title']); ?></h4>
                                    <div class="apply-similar-company">
                                        <?php echo htmlspecialchars($similarJob['company_name']); ?> · <?php echo htmlspecialchars($similarJob['company_city']); ?>
                                    </div>
                                    <div class="apply-similar-meta">
                                        <span class="apply-similar-tag"><?php echo htmlspecialchars($similarJob['employment_type']); ?></span>
                                        <span class="apply-similar-tag"><?php echo htmlspecialchars($similarJob['work_mode']); ?></span>
                                    </div>
                                    <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $similarJob['id']; ?>" 
                                       class="apply-similar-link">
                                        View Job
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.apply-tab-panel');
    contents.forEach(content => content.classList.add('hidden'));
    
    // Remove active class from all tab buttons
    const buttons = document.querySelectorAll('.apply-tab');
    buttons.forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.add('active');
}
</script>

<?php require_once '../includes/footer.php'; ?>