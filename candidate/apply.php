<?php
require_once '../includes/auth.php';
requireRole('candidate');
$pageTitle = "Apply for Job - Apex Nexus";
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = $_POST['job_id'] ?? '';
    $coverLetter = $_POST['cover_letter'] ?? '';
    
    if (empty($jobId) || empty($candidateId)) {
        setFlash('error', 'Invalid request');
        redirect('/apex-nexus-portal/candidate/search-jobs.php');
    }
    
    // Validate job exists and is active
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'active' AND is_deleted = 0");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        setFlash('error', 'Job not found');
        redirect('/apex-nexus-portal/candidate/search-jobs.php');
    }
    
    // Check if already applied
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND candidate_id = ? AND is_deleted = 0");
    $stmt->execute([$jobId, $candidateId]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        setFlash('error', 'You have already applied for this job');
        redirect('/apex-nexus-portal/candidate/job-detail.php?id=' . $jobId);
    }
    
    // Check deadline
    if (!empty($job['deadline']) && strtotime($job['deadline']) < time()) {
        setFlash('error', 'Application deadline has passed');
        redirect('/apex-nexus-portal/candidate/job-detail.php?id=' . $jobId);
    }
    
    // Insert application
    $stmt = $pdo->prepare("
        INSERT INTO applications (job_id, candidate_id, cover_letter, status, created_at) 
        VALUES (?, ?, ?, 'applied', NOW())
    ");
    
    if ($stmt->execute([$jobId, $candidateId, $coverLetter])) {
        setFlash('success', 'Application submitted successfully!');
        redirect('/apex-nexus-portal/candidate/my-applications.php');
    } else {
        setFlash('error', 'Failed to submit application. Please try again.');
        redirect('/apex-nexus-portal/candidate/apply.php?job_id=' . $jobId);
    }
}

// Get job details for display
$jobId = $_GET['job_id'] ?? '';
if (empty($jobId) || !is_numeric($jobId)) {
    setFlash('error', 'Invalid job ID');
    redirect('/apex-nexus-portal/candidate/search-jobs.php');
}

$stmt = $pdo->prepare("
    SELECT j.*, c.company_name, c.city as company_city, c.state as company_state
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

// Check if already applied
$alreadyApplied = false;
if ($candidateId) {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND candidate_id = ? AND is_deleted = 0");
    $stmt->execute([$jobId, $candidateId]);
    $alreadyApplied = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

if ($alreadyApplied) {
    setFlash('error', 'You have already applied for this job');
    redirect('/apex-nexus-portal/candidate/job-detail.php?id=' . $jobId);
}
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
        
        <div class="max-w-2xl mx-auto">
                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-blue-600"><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($job['title']); ?></h2>
                    <div class="text-gray-600"><?php echo htmlspecialchars($job['company_name']); ?></div>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <span class="tag tag-blue"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                <span class="tag tag-green"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                <span class="tag"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
                    <span class="tag bg-purple-50 text-purple-700"><?php echo htmlspecialchars($job['salary']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Application Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">Submit Your Application</h3>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                
                <!-- Resume Section -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Resume</h4>
                    <?php if (!empty($candidate['resume'])): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-green-800">Using current resume</div>
                                    <div class="text-sm text-green-600"><?php echo htmlspecialchars(basename($candidate['resume'])); ?></div>
                                </div>
                                <a href="/apex-nexus-portal/candidate/upload-resume.php" 
                                   class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Upload new resume
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-amber-800">No resume uploaded</div>
                                    <div class="text-sm text-amber-600">Please upload your resume first</div>
                                </div>
                                <a href="/apex-nexus-portal/candidate/upload-resume.php" 
                                   class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                    Upload Resume
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Cover Letter -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Cover Letter (Optional)</h4>
                    <div class="relative">
                        <textarea name="cover_letter" 
                                  id="coverLetter"
                                  maxlength="500"
                                  rows="5"
                                  placeholder="Tell the employer why you're interested in this position and what makes you a great fit..."
                                  class="search-input w-full resize-none"><?php echo htmlspecialchars($_POST['cover_letter'] ?? ''); ?></textarea>
                        <div class="absolute bottom-2 right-2 text-xs text-gray-500">
                            <span id="charCount">0</span>/500
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        Briefly introduce yourself and explain why you're interested in this role.
                    </p>
                </div>
                
                <!-- Terms -->
                <div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="terms" required 
                               class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm text-gray-600">
                            I confirm that all information provided is accurate and complete. 
                            I understand that false statements may disqualify me from employment or result in termination if employed.
                        </span>
                    </label>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-4 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Submit Application
                    </button>
                    <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $job['id']; ?>" 
                       class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-medium text-center hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Application Tips -->
        <div class="bg-blue-50 rounded-2xl p-6 border border-blue-200 mt-6">
            <h4 class="font-medium text-blue-800 mb-3">Application Tips</h4>
            <ul class="space-y-2 text-sm text-blue-700">
                <li>?</li>
                <li>Highlight relevant skills and experience that match the job requirements</li>
                <li>Keep your cover letter concise and focused on the specific role</li>
                <li>Proofread your application before submitting</li>
                <li>Submit your application before the deadline for better consideration</li>
            </ul>
        </div>
        
    </div>

  </main>
</div>

<script>
// Character counter for cover letter
document.addEventListener('DOMContentLoaded', function() {
    const coverLetter = document.getElementById('coverLetter');
    const charCount = document.getElementById('charCount');
    
    if (coverLetter && charCount) {
        function updateCharCount() {
            const count = coverLetter.value.length;
            charCount.textContent = count;
            
            if (count > 450) {
                charCount.classList.add('text-amber-600');
                charCount.classList.remove('text-gray-500');
            } else {
                charCount.classList.remove('text-amber-600');
                charCount.classList.add('text-gray-500');
            }
        }
        
        coverLetter.addEventListener('input', updateCharCount);
        updateCharCount(); // Initialize
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>