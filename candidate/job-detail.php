<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
requireRole('candidate');
$pageTitle = "Job Details - Apex Nexus";
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
        
        <!-- Job Header -->
        <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="flex items-center gap-4 text-gray-600">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-blue-600"><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                            </div>
                            <span class="font-medium"><?php echo htmlspecialchars($job['company_name']); ?></span>
                <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                <div class="flex items-center gap-4 text-gray-600">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                            <span class="text-xs font-medium text-blue-600"><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                        </div>
                        <span class="font-medium"><?php echo htmlspecialchars($job['company_name']); ?></span>
                    </div>
                    <span>·</span>
                    <span><?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?></span>
                </div>
            </div>
            
            <?php if ($alreadyApplied): ?>
                <div class="bg-green-50 text-green-600 border border-green-200 px-4 py-2 rounded-lg font-medium">
                    You have already applied for this job ?
                </div>
            <?php else: ?>
                <a href="/apex-nexus-portal/candidate/apply.php?job_id=<?php echo $job['id']; ?>" 
                   class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Apply for this Job
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Tags -->
        <div class="flex flex-wrap gap-2 mb-4">
            <span class="tag tag-blue"><?php echo htmlspecialchars($job['employment_type']); ?></span>
            <span class="tag tag-green"><?php echo htmlspecialchars($job['work_mode']); ?></span>
            <span class="tag"><?php echo htmlspecialchars($job['experience_required']); ?></span>
            <?php if (!empty($job['deadline'])): ?>
                <span class="tag bg-amber-50 text-amber-700">Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Salary -->
        <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
            <div class="text-lg font-semibold text-gray-800 mb-2">
                <?php echo htmlspecialchars($job['salary']); ?>
            </div>
        <?php else: ?>
            <div class="text-gray-500 mb-2">Salary not disclosed</div>
        <?php endif; ?>
        
        <!-- Posted Date -->
        <div class="text-sm text-gray-500">
            Posted <?php echo timeAgo($job['created_at']); ?>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100">
                
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button onclick="showTab('overview')" id="tab-overview" 
                                class="tab-button active py-4 px-6 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            Overview
                        </button>
                        <button onclick="showTab('requirements')" id="tab-requirements" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Requirements
                        </button>
                        <button onclick="showTab('responsibilities')" id="tab-responsibilities" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Responsibilities
                        </button>
                        <button onclick="showTab('benefits')" id="tab-benefits" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Perks & Benefits
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Overview Tab -->
                    <div id="content-overview" class="tab-content">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Job Overview</h3>
                        <div class="prose max-w-none text-gray-600">
                            <?php echo !empty($job['description']) ? nl2br(htmlspecialchars($job['description'])) : '<p class="text-gray-500">No description available.</p>'; ?>
                        </div>
                    </div>
                    
                    <!-- Requirements Tab -->
                    <div id="content-requirements" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Requirements</h3>
                        <div class="prose max-w-none text-gray-600">
                            <?php echo !empty($job['requirements']) ? nl2br(htmlspecialchars($job['requirements'])) : '<p class="text-gray-500">No specific requirements listed.</p>'; ?>
                        </div>
                    </div>
                    
                    <!-- Responsibilities Tab -->
                    <div id="content-responsibilities" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Responsibilities</h3>
                        <div class="prose max-w-none text-gray-600">
                            <?php echo !empty($job['responsibilities']) ? nl2br(htmlspecialchars($job['responsibilities'])) : '<p class="text-gray-500">No responsibilities listed.</p>'; ?>
                        </div>
                    </div>
                    
                    <!-- Benefits Tab -->
                    <div id="content-benefits" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Perks & Benefits</h3>
                        <div class="prose max-w-none text-gray-600">
                            <?php echo !empty($job['perks']) ? nl2br(htmlspecialchars($job['perks'])) : '<p class="text-gray-500">No perks and benefits listed.</p>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Company Info -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <h3 class="font-semibold text-gray-800 mb-4">Company Information</h3>
                <div class="space-y-3">
                    <div>
                        <div class="text-sm text-gray-500">Company Name</div>
                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($job['company_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Location</div>
                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($job['company_city'] . ', ' . $job['company_state']); ?></div>
                    </div>
                    <?php if (!empty($job['company_website'])): ?>
                        <div>
                            <div class="text-sm text-gray-500">Website</div>
                            <a href="<?php echo htmlspecialchars($job['company_website']); ?>" 
                               target="_blank" class="text-blue-600 hover:text-blue-700 text-sm">
                                <?php echo htmlspecialchars($job['company_website']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Job Summary -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <h3 class="font-semibold text-gray-800 mb-4">Job Summary</h3>
                <div class="space-y-3">
                    <div>
                        <div class="text-sm text-gray-500">Posted</div>
                        <div class="font-medium text-gray-800"><?php echo timeAgo($job['created_at']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Deadline</div>
                        <div class="font-medium text-gray-800">
                            <?php echo !empty($job['deadline']) ? date('M j, Y', strtotime($job['deadline'])) : 'Open'; ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Experience Required</div>
                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($job['experience_required']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Employment Type</div>
                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($job['employment_type']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Work Mode</div>
                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($job['work_mode']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Similar Jobs -->
            <?php if (!empty($similarJobs)): ?>
                <div class="bg-white rounded-2xl p-6 border border-gray-100">
                    <h3 class="font-semibold text-gray-800 mb-4">Similar Jobs</h3>
                    <div class="space-y-4">
                        <?php foreach ($similarJobs as $similarJob): ?>
                            <div class="border-b border-gray-100 pb-4 last:border-0">
                                <h4 class="font-medium text-gray-800 mb-1"><?php echo htmlspecialchars($similarJob['title']); ?></h4>
                                <div class="text-sm text-gray-600 mb-2">
                                    <?php echo htmlspecialchars($similarJob['company_name']); ?> 
                                    <span class="mx-1">·</span>
                                    <?php echo htmlspecialchars($similarJob['company_city']); ?>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex gap-2">
                                        <span class="tag text-xs"><?php echo htmlspecialchars($similarJob['employment_type']); ?></span>
                                        <span class="tag text-xs"><?php echo htmlspecialchars($similarJob['work_mode']); ?></span>
                                    </div>
                                    <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $similarJob['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>

  </main>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.add('hidden'));
    
    // Remove active class from all tab buttons
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
}
</script>

<?php require_once '../includes/footer.php'; ?>