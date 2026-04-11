<?php
require_once '../includes/auth.php';
require_once '../includes/urls.php';
requireRole('candidate');
$pageTitle = "Upload Resume - Apex Nexus";
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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $uploadDir = '../assets/uploads/resumes/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['resume'];
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Debug: Log file information
    error_log("Upload attempt - File: " . $file['name'] . ", Type: " . $file['type'] . ", Size: " . $file['size']);
    error_log("User ID: " . $userId);
    
    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
        $filename = time() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("File moved successfully to: " . $filepath);
            
            // Check if candidate record exists
            $checkStmt = $pdo->prepare("SELECT id FROM candidates WHERE user_id = ? AND is_deleted = 0");
            $checkStmt->execute([$userId]);
            $existingCandidate = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCandidate) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE candidates SET resume = ?, updated_at = NOW()
                    WHERE user_id = ? AND is_deleted = 0
                ");
                $result = $stmt->execute(['assets/uploads/resumes/' . $filename, $userId]);
                
                if ($result) {
                    error_log("Database updated successfully for user: " . $userId);
                    setFlash('success', 'Resume uploaded successfully!');
                } else {
                    error_log("Database update failed for user: " . $userId);
                    setFlash('error', 'Failed to update database. Please try again.');
                }
            } else {
                // Create candidate record if it doesn't exist
                error_log("Creating new candidate record for user: " . $userId);
                
                // Get user information
                $userStmt = $pdo->prepare("SELECT email FROM users WHERE id = ? AND is_deleted = 0");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $createStmt = $pdo->prepare("
                        INSERT INTO candidates (user_id, email, resume, created_at, updated_at, is_deleted)
                        VALUES (?, ?, ?, NOW(), NOW(), 0)
                    ");
                    $result = $createStmt->execute([$userId, $user['email'], 'assets/uploads/resumes/' . $filename]);
                    
                    if ($result) {
                        error_log("Candidate record created successfully for user: " . $userId);
                        setFlash('success', 'Resume uploaded successfully!');
                    } else {
                        error_log("Failed to create candidate record for user: " . $userId);
                        setFlash('error', 'Failed to create candidate record. Please try again.');
                    }
                } else {
                    error_log("User not found for ID: " . $userId);
                    setFlash('error', 'User account not found. Please contact support.');
                }
            }
            
            redirect($CANDIDATE_URL . '/upload-resume.php');
        } else {
            error_log("Failed to move uploaded file from: " . $file['tmp_name'] . " to: " . $filepath);
            setFlash('error', 'Failed to upload resume file. Please check file permissions.');
        }
    } else {
        $error = 'Invalid file type or size. ';
        if ($file['size'] > $maxSize) {
            $error .= 'File size must be less than 5MB.';
        } else {
            $error .= 'Please upload a PDF, DOC, or DOCX file.';
        }
        error_log("Upload validation failed: " . $error);
        setFlash('error', $error);
    }
    
    redirect($CANDIDATE_URL . '/upload-resume.php');
}
?>

<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate-nav.css">
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate-modern.css">

<style>
/* Modern Resume Upload Styles */
.resume-upload-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 80vh;
    border-radius: 20px;
    overflow: hidden;
}

.upload-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-right: 1px solid rgba(255, 255, 255, 0.2);
}

.preview-section {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
}

.drop-zone {
    border: 2px dashed #cbd5e0;
    border-radius: 15px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f6f9fc 0%, #e9ecef 100%);
    position: relative;
    overflow: hidden;
}

.drop-zone::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.drop-zone:hover::before,
.drop-zone.dragover::before {
    opacity: 1;
}

.drop-zone:hover,
.drop-zone.dragover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
}

.file-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.drop-zone:hover .file-icon {
    transform: scale(1.1);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.preview-frame {
    width: 100%;
    height: 600px;
    border: none;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.upload-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
}

.upload-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.upload-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.file-info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-left: 4px solid #667eea;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.resume-tips {
    background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%);
    border-radius: 15px;
    padding: 25px;
    margin-top: 20px;
}

.tip-item {
    display: flex;
    align-items: start;
    margin: 15px 0;
    color: #5e35b1;
}

.tip-item::before {
    content: '>';
    margin-right: 10px;
    font-weight: bold;
    color: #667eea;
}

.current-resume-card {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8ff 100%);
    border-left: 4px solid #4caf50;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
}

.preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: #9ca3af;
    text-align: center;
}

.preview-placeholder svg {
    width: 100px;
    height: 100px;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .resume-upload-container {
        flex-direction: column;
    }
    
    .upload-section,
    .preview-section {
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .preview-frame {
        height: 400px;
    }
}
</style>

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container p-6">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">Upload Resume</h1>
            <p class="text-white/80 text-lg">Upload your resume to complete your profile</p>
        </div>
        
        <!-- Main Upload Container -->
        <div class="resume-upload-container grid grid-cols-1 lg:grid-cols-2 gap-0 shadow-2xl">
            
            <!-- Left Column: Upload Form -->
            <div class="upload-section p-8">
                
                <!-- Current Resume Status -->
                <?php if (!empty($candidate['resume'])): ?>
                    <div class="current-resume-card">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Current Resume
                        </h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1">
                                    <?php echo htmlspecialchars(basename($candidate['resume'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Uploaded: <?php echo date('M j, Y', strtotime($candidate['updated_at'] ?? '')); ?>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="<?php echo $UPLOADS_URL; ?>/resumes/<?php echo htmlspecialchars(basename($candidate['resume'])); ?>" 
                                   download class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">
                        <?php echo !empty($candidate['resume']) ? 'Update Resume' : 'Upload Resume'; ?>
                    </h3>
                    
                    <!-- Drag & Drop Zone -->
                    <div class="drop-zone" id="dropZone">
                        <div class="file-icon">
                            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="text-gray-700">
                            <p class="text-xl font-semibold mb-2">Drop your resume here</p>
                            <p class="text-sm">or click to browse</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">PDF, DOC, or DOCX (max 5MB)</p>
                        <input type="file" name="resume" id="resumeInput" accept=".pdf,.doc,.docx" class="hidden" required>
                    </div>
                    
                    <!-- File Info (shown when file is selected) -->
                    <div id="fileInfo" class="file-info-card hidden">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-gray-800 mb-1" id="fileName"></div>
                                <div class="text-xs text-gray-600" id="fileSize"></div>
                            </div>
                            <button type="button" onclick="clearFile()" class="text-red-600 hover:text-red-700 bg-red-50 p-2 rounded-lg">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" 
                            class="upload-button w-full py-4 text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed" 
                            disabled>
                        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Upload Resume
                    </button>
                </form>
                
                <!-- Resume Tips -->
                <div class="resume-tips">
                    <h4 class="font-bold text-gray-800 mb-4 text-lg">
                        <svg class="w-5 h-5 inline mr-2 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        Resume Tips
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="tip-item">Keep your resume under 2 pages for most positions</div>
                        <div class="tip-item">Use PDF format for best compatibility</div>
                        <div class="tip-item">Include relevant keywords for your target roles</div>
                        <div class="tip-item">Proofread carefully for typos and grammatical errors</div>
                        <div class="tip-item">Ensure your contact information is up to date</div>
                    </div>
                </div>
                
                <!-- Back to Profile -->
                <div class="text-center mt-6">
                    <a href="<?php echo $CANDIDATE_URL; ?>/profile.php" 
                       class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Back to Profile
                    </a>
                </div>
                
            </div>
            
            <!-- Right Column: Preview -->
            <div class="preview-section p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Resume Preview</h3>
                
                <!-- Preview Container -->
                <div id="previewContainer">
                    <?php if (!empty($candidate['resume'])): ?>
                        <?php
                        $resumePath = '../' . $candidate['resume'];
                        $fileExtension = strtolower(pathinfo($candidate['resume'], PATHINFO_EXTENSION));
                        
                        if ($fileExtension === 'pdf' && file_exists($resumePath)): ?>
                            <iframe src="<?php echo $UPLOADS_URL; ?>/resumes/<?php echo htmlspecialchars(basename($candidate['resume'])); ?>#view=FitH" 
                                    class="preview-frame" 
                                    title="Resume Preview">
                            </iframe>
                        <?php else: ?>
                            <div class="preview-placeholder">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                </svg>
                                <h4 class="text-xl font-semibold mb-2">Resume File</h4>
                                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars(basename($candidate['resume'])); ?></p>
                                <a href="<?php echo $UPLOADS_URL; ?>/resumes/<?php echo htmlspecialchars(basename($candidate['resume'])); ?>" 
                                   download class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    Download Resume
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="preview-placeholder">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <h4 class="text-xl font-semibold mb-2">No Resume Uploaded</h4>
                            <p class="text-gray-600">Upload your resume to see a preview here</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Selected File Preview -->
                <div id="selectedFilePreview" class="hidden">
                    <div class="preview-placeholder">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <h4 class="text-xl font-semibold mb-2">File Selected</h4>
                        <p class="text-gray-600" id="selectedFileName"></p>
                        <p class="text-sm text-gray-500 mt-2">Preview will be available after upload</p>
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('resumeInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');
    const previewContainer = document.getElementById('previewContainer');
    const selectedFilePreview = document.getElementById('selectedFilePreview');
    const selectedFileName = document.getElementById('selectedFileName');
    const form = document.querySelector('form');
    
    // Add form submission event listener
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Check if file is selected
        if (!fileInput.files || fileInput.files.length === 0) {
            showNotification('Please select a file to upload.', 'error');
            return;
        }
        
        // Submit the form
        const formData = new FormData(form);
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-5 h-5 inline mr-2 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
            </svg>
            Uploading...
        `;
        
        // Submit form via fetch
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Reload page to show updated content
            window.location.reload();
        })
        .catch(error => {
            console.error('Upload error:', error);
            showNotification('Upload failed. Please try again.', 'error');
            
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Upload Resume
            `;
        });
    });
    
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
            showNotification('Please upload a PDF, DOC, or DOCX file.', 'error');
            return;
        }
        
        if (file.size > maxSize) {
            showNotification('File size must be less than 5MB.', 'error');
            return;
        }
        
        // Show file info
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.classList.remove('hidden');
        submitBtn.disabled = false;
        
        // Show selected file preview
        selectedFileName.textContent = file.name;
        previewContainer.classList.add('hidden');
        selectedFilePreview.classList.remove('hidden');
        
        // Update drop zone content without breaking structure
        const dropZoneContent = dropZone.querySelector('.text-center') || dropZone.querySelector('div');
        if (dropZoneContent) {
            dropZoneContent.innerHTML = `
                <div class="file-icon">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="text-green-700">
                    <p class="text-xl font-semibold mb-2">File Selected</p>
                    <p class="text-sm">${file.name}</p>
                    <p class="text-xs mt-1">${formatFileSize(file.size)}</p>
                </div>
            `;
        }
        
        // Show success notification
        showNotification('File selected successfully! Click Upload Resume to continue.', 'success');
    }
    
    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        submitBtn.disabled = true;
        previewContainer.classList.remove('hidden');
        selectedFilePreview.classList.add('hidden');
        
        // Reset drop zone content without breaking structure
        const dropZoneContent = dropZone.querySelector('.text-center') || dropZone.querySelector('div');
        if (dropZoneContent) {
            dropZoneContent.innerHTML = `
                <div class="file-icon">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="text-gray-700">
                    <p class="text-xl font-semibold mb-2">Drop your resume here</p>
                    <p class="text-sm">or click to browse</p>
                </div>
                <p class="text-xs text-gray-500 mt-3">PDF, DOC, or DOCX (max 5MB)</p>
            `;
        }
        
        showNotification('File cleared. Please select a new file.', 'info');
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 flex items-center space-x-3 transform translate-x-full transition-transform duration-300`;
        
        // Set color based on type
        if (type === 'success') {
            notification.classList.add('bg-green-500', 'text-white');
        } else if (type === 'error') {
            notification.classList.add('bg-red-500', 'text-white');
        } else {
            notification.classList.add('bg-blue-500', 'text-white');
        }
        
        notification.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' : 
                  type === 'error' ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>' :
                  '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>'}
            </svg>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
