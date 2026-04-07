<?php
require_once '../includes/auth.php';
requireRole('candidate');
$pageTitle = "Upload Resume - Apex Nexus";
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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $uploadDir = '../assets/uploads/resumes/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['resume'];
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
        $filename = time() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            $stmt = $pdo->prepare("
                UPDATE candidates SET resume = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            $stmt->execute(['assets/uploads/resumes/' . $filename, $userId]);
            
            setFlash('success', 'Resume uploaded successfully!');
            redirect('/apex-nexus-portal/candidate/upload-resume.php');
        } else {
            setFlash('error', 'Failed to upload resume. Please try again.');
        }
    } else {
        $error = 'Invalid file type or size. ';
        if ($file['size'] > $maxSize) {
            $error .= 'File size must be less than 5MB.';
        } else {
            $error .= 'Please upload a PDF, DOC, or DOCX file.';
        }
        setFlash('error', $error);
    }
    
    redirect('/apex-nexus-portal/candidate/upload-resume.php');
}
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
        
        <div class="max-w-lg mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Upload Resume</h1>
            <p class="text-gray-600">Upload your resume to complete your profile</p>
        </div>
        
        <!-- Current Resume -->
        <?php if (!empty($candidate['resume'])): ?>
            <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Resume</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-green-800 mb-1">
                                <?php echo htmlspecialchars(basename($candidate['resume'])); ?>
                            </div>
                            <div class="text-xs text-green-600">
                                Uploaded: <?php echo date('M j, Y', strtotime($candidate['updated_at'] ?? '')); ?>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="/apex-nexus-portal/<?php echo htmlspecialchars($candidate['resume']); ?>" 
                               download class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Upload Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">
                <?php echo !empty($candidate['resume']) ? 'Upload New Resume' : 'Upload Resume'; ?>
            </h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Drag & Drop Zone -->
                <div class="drop-zone" id="dropZone">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-gray-600 mb-2">
                            <p class="text-lg font-medium">Drop your resume here</p>
                            <p class="text-sm">or click to browse</p>
                        </div>
                        <p class="text-xs text-gray-500">PDF, DOC, or DOCX (max 5MB)</p>
                    </div>
                    <input type="file" name="resume" id="resumeInput" accept=".pdf,.doc,.docx" class="hidden" required>
                </div>
                
                <!-- File Info (shown when file is selected) -->
                <div id="fileInfo" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-blue-800" id="fileName"></div>
                            <div class="text-xs text-blue-600" id="fileSize"></div>
                        </div>
                        <button type="button" onclick="clearFile()" class="text-blue-600 hover:text-blue-700">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" id="submitBtn" 
                        class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed" 
                        disabled>
                    Upload Resume
                </button>
            </form>
        </div>
        
        <!-- Tips -->
        <div class="bg-blue-50 rounded-2xl p-6 border border-blue-200 mt-6">
            <h4 class="font-medium text-blue-800 mb-3">Resume Tips</h4>
            <ul class="space-y-2 text-sm text-blue-700">
                <li>?</li>
                <li>Keep your resume under 2 pages for most positions</li>
                <li>Use PDF format for best compatibility</li>
                <li>Include relevant keywords for your target roles</li>
                <li>Proofread carefully for typos and grammatical errors</li>
                <li>Ensure your contact information is up to date</li>
            </ul>
        </div>
        
        <!-- Back to Profile -->
        <div class="text-center mt-6">
            <a href="/apex-nexus-portal/candidate/profile.php" 
               class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                Back to Profile
            </a>
        </div>
        
    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('resumeInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');
    
    // Click to browse
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Drag and drop
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });
    
    // File selection
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });
    
    function handleFile(file) {
        // Validate file type
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload a PDF, DOC, or DOCX file.');
            return;
        }
        
        if (file.size > maxSize) {
            alert('File size must be less than 5MB.');
            return;
        }
        
        // Show file info
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.classList.remove('hidden');
        submitBtn.disabled = false;
        
        // Update drop zone text
        dropZone.innerHTML = `
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto text-green-500 mb-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-green-600">
                    <p class="text-lg font-medium">File selected</p>
                    <p class="text-sm">${file.name}</p>
                </div>
            </div>
        `;
    }
    
    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        submitBtn.disabled = true;
        
        // Reset drop zone
        dropZone.innerHTML = `
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-gray-600 mb-2">
                    <p class="text-lg font-medium">Drop your resume here</p>
                    <p class="text-sm">or click to browse</p>
                </div>
                <p class="text-xs text-gray-500">PDF, DOC, or DOCX (max 5MB)</p>
            </div>
        `;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>