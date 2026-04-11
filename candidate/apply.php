<?php
require_once '../includes/auth.php';
requireRole('candidate');
$pageTitle = "Apply for Job - Apex Nexus";
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = $_POST['job_id'] ?? '';
    $coverLetter = $_POST['cover_letter'] ?? '';
    $resumeOption = $_POST['resume_option'] ?? 'current';
    $resumePath = '';
    
    if (empty($jobId) || empty($candidateId)) {
        setFlash('error', 'Invalid request');
        redirect('/apex-nexus-portal/candidate/search-jobs.php');
    }
    
    // Handle resume upload if new resume is selected
    if ($resumeOption === 'new' && isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/resumes/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['resume']['name']);
        $fileName = $candidateId . '_' . time() . '.' . strtolower($fileInfo['extension']);
        $targetPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['pdf', 'doc', 'docx'];
        if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
            setFlash('error', 'Invalid file type. Please upload PDF, DOC, or DOCX files only.');
            redirect('/apex-nexus-portal/candidate/apply.php?job_id=' . $jobId);
        }
        
        // Validate file size (max 5MB)
        if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
            setFlash('error', 'File size too large. Please upload a file smaller than 5MB.');
            redirect('/apex-nexus-portal/candidate/apply.php?job_id=' . $jobId);
        }
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
            $resumePath = 'assets/uploads/resumes/' . $fileName;
            
            // Update candidate's resume
            $stmt = $pdo->prepare("UPDATE candidates SET resume = ? WHERE id = ?");
            $stmt->execute([$resumePath, $candidateId]);
        } else {
            setFlash('error', 'Failed to upload resume. Please try again.');
            redirect('/apex-nexus-portal/candidate/apply.php?job_id=' . $jobId);
        }
    } elseif ($resumeOption === 'current' && !empty($candidate['resume'])) {
        $resumePath = $candidate['resume'];
    } elseif ($resumeOption === 'new') {
        setFlash('error', 'Please select a resume file to upload.');
        redirect('/apex-nexus-portal/candidate/apply.php?job_id=' . $jobId);
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
    
    // Insert application with resume information
    $stmt = $pdo->prepare("
        INSERT INTO applications (job_id, candidate_id, cover_letter, resume, status, created_at) 
        VALUES (?, ?, ?, ?, 'applied', NOW())
    ");
    
    if ($stmt->execute([$jobId, $candidateId, $coverLetter, $resumePath])) {
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
        
        <!-- Job Information Card -->
        <div class="apply-job-card">
            <div class="apply-job-header">
                <div class="apply-company-logo">
                    <span><?php echo substr(htmlspecialchars($job['company_name']), 0, 2); ?></span>
                </div>
                <div class="apply-job-details">
                    <h1 class="apply-job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <p class="apply-company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                    <div class="apply-job-meta">
                        <span class="apply-meta-item"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                        <span class="apply-meta-item"><?php echo htmlspecialchars($job['work_mode']); ?></span>
                        <span class="apply-meta-item"><?php echo htmlspecialchars($job['experience_required']); ?></span>
                        <?php if ($job['salary_visible'] && !empty($job['salary'])): ?>
                            <span class="apply-meta-item salary"><?php echo htmlspecialchars($job['salary']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Application Form -->
        <div class="apply-form-card">
            <div class="apply-form-header">
                <h2 class="apply-form-title">Application Details</h2>
                <p class="apply-form-subtitle">Please provide your information to apply</p>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="apply-form">
                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                
                <!-- Resume Selection -->
                <div class="apply-form-section">
                    <label class="apply-section-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Resume
                    </label>
                    
                    <div class="apply-resume-options">
                        <?php if (!empty($candidate['resume'])): ?>
                            <label class="apply-resume-option">
                                <input type="radio" name="resume_option" value="current" checked class="apply-resume-radio">
                                <div class="apply-resume-card">
                                    <div class="apply-resume-icon">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="apply-resume-info">
                                        <div class="apply-resume-name">Current Resume</div>
                                        <div class="apply-resume-file"><?php echo htmlspecialchars(basename($candidate['resume'])); ?></div>
                                    </div>
                                    <div class="apply-resume-check">
                                        <div class="apply-check-dot"></div>
                                    </div>
                                </div>
                            </label>
                        <?php endif; ?>
                        
                        <label class="apply-resume-option">
                            <input type="radio" name="resume_option" value="new" class="apply-resume-radio" <?php echo empty($candidate['resume']) ? 'checked' : ''; ?>>
                            <div class="apply-resume-card">
                                <div class="apply-resume-icon upload">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <div class="apply-resume-info">
                                    <div class="apply-resume-name">Upload New Resume</div>
                                    <div class="apply-resume-file">Choose a file (PDF, DOC, DOCX)</div>
                                </div>
                                <div class="apply-resume-check">
                                    <div class="apply-check-dot"></div>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="apply-upload-area" id="newResumeUpload" style="display: <?php echo empty($candidate['resume']) ? 'block' : 'none'; ?>;">
                        <div class="apply-upload-zone">
                            <input type="file" name="resume" id="resumeFile" accept=".pdf,.doc,.docx" class="apply-file-input">
                            <div class="apply-upload-content">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="apply-upload-text">Click to upload or drag and drop</p>
                                <p class="apply-upload-subtext">Maximum file size: 5MB</p>
                            </div>
                        </div>
                        <div class="apply-file-preview" id="filePreview" style="display: none;">
                            <div class="apply-preview-info">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="apply-preview-details">
                                    <div class="apply-preview-name" id="fileName"></div>
                                    <div class="apply-preview-size" id="fileSize"></div>
                                </div>
                                <button type="button" class="apply-remove-file" onclick="removeFile()">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cover Letter -->
                <div class="apply-form-section">
                    <label class="apply-section-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Cover Letter (Optional)
                    </label>
                    <div class="apply-textarea-container">
                        <textarea name="cover_letter" 
                                  id="coverLetter"
                                  maxlength="500"
                                  rows="4"
                                  placeholder="Tell the employer why you're interested in this position..."
                                  class="apply-textarea"><?php echo htmlspecialchars($_POST['cover_letter'] ?? ''); ?></textarea>
                        <div class="apply-char-count">
                            <span id="charCount">0</span>/500
                        </div>
                    </div>
                    <p class="apply-help-text">Briefly introduce yourself and explain why you're interested in this role.</p>
                </div>
                
                <!-- Terms and Submit -->
                <div class="apply-form-actions">
                    <label class="apply-terms-label">
                        <input type="checkbox" name="terms" required class="apply-terms-checkbox">
                        <span class="apply-terms-text">
                            I confirm that all information provided is accurate and complete.
                        </span>
                    </label>
                    
                    <div class="apply-button-group">
                        <button type="submit" class="apply-submit-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Submit Application
                        </button>
                        <a href="/apex-nexus-portal/candidate/job-detail.php?id=<?php echo $job['id']; ?>" class="apply-cancel-btn">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tips Section -->
        <div class="apply-tips-card">
            <h3 class="apply-tips-title">Application Tips</h3>
            <div class="apply-tips-list">
                <div class="apply-tip-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Highlight relevant skills and experience</span>
                </div>
                <div class="apply-tip-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Keep your cover letter concise and focused</span>
                </div>
                <div class="apply-tip-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Proofread your application before submitting</span>
                </div>
                <div class="apply-tip-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Submit before the deadline for better consideration</span>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
// Character counter for cover letter and resume upload functionality
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for cover letter
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
    
    // Resume upload functionality
    const resumeRadios = document.querySelectorAll('input[name="resume_option"]');
    const newResumeUpload = document.getElementById('newResumeUpload');
    const resumeFile = document.getElementById('resumeFile');
    const filePreview = document.getElementById('filePreview');
    const uploadZone = document.querySelector('.apply-upload-zone');
    
    // Handle radio button changes
    resumeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'new') {
                newResumeUpload.style.display = 'block';
            } else {
                newResumeUpload.style.display = 'none';
                resetFileUpload();
            }
        });
    });
    
    // Handle file selection
    if (resumeFile) {
        resumeFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                displayFilePreview(file);
            }
        });
    }
    
    // Handle drag and drop
    if (uploadZone) {
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#4f46e5';
            this.style.background = '#f0f9ff';
        });
        
        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#d1d5db';
            this.style.background = '#f9fafb';
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#d1d5db';
            this.style.background = '#f9fafb';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                resumeFile.files = files;
                displayFilePreview(files[0]);
            }
        });
    }
    
    function displayFilePreview(file) {
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        if (fileName && fileSize) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
        }
        
        if (uploadZone) {
            uploadZone.style.display = 'none';
        }
        if (filePreview) {
            filePreview.style.display = 'block';
        }
    }
    
    function resetFileUpload() {
        if (resumeFile) {
            resumeFile.value = '';
        }
        if (uploadZone) {
            uploadZone.style.display = 'block';
            uploadZone.style.borderColor = '#d1d5db';
            uploadZone.style.background = '#f9fafb';
        }
        if (filePreview) {
            filePreview.style.display = 'none';
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Remove file function
    window.removeFile = function() {
        resetFileUpload();
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>