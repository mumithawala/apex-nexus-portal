<?php
require_once '../includes/auth.php';
require_once '../includes/candidate-helpers.php';
requireRole('candidate');
$pageTitle = "My Profile - Apex Nexus";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? '';
    
    switch ($tab) {
        case 'personal':
            // Update personal information
            $stmt = $pdo->prepare("
                UPDATE candidates SET 
                    full_name = ?, phone = ?, date_of_birth = ?, 
                    gender = ?, city = ?, state = ?, country = ?, nationality = ?,
                    linkedin_url = ?, portfolio_url = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            
            $stmt->execute([
                ($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''),
                $_POST['phone'] ?? '',
                $_POST['date_of_birth'] ?? '',
                $_POST['gender'] ?? '',
                $_POST['city'] ?? '',
                $_POST['state'] ?? '',
                $_POST['country'] ?? '',
                $_POST['nationality'] ?? '',
                $_POST['linkedin_url'] ?? '',
                $_POST['portfolio_url'] ?? '',
                $userId
            ]);
            
            setFlash('success', 'Personal information updated successfully!');
            break;
            
        case 'professional':
            // Update professional information
            $stmt = $pdo->prepare("
                UPDATE candidates SET 
                    current_job_title = ?, current_company = ?, total_experience = ?,
                    notice_period = ?, job_type = ?, preferred_location = ?,
                    current_salary = ?, expected_salary = ?, highest_qualification = ?,
                    updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            
            $stmt->execute([
                $_POST['current_job_title'] ?? '',
                $_POST['current_company'] ?? '',
                $_POST['total_experience'] ?? '',
                $_POST['notice_period'] ?? '',
                $_POST['job_type'] ?? '',
                $_POST['preferred_location'] ?? '',
                $_POST['current_salary'] ?? '',
                $_POST['expected_salary'] ?? '',
                $_POST['highest_qualification'] ?? '',
                $userId
            ]);
            
            setFlash('success', 'Professional information updated successfully!');
            break;
            
        case 'skills':
            // Update skills
            $skills = isset($_POST['skills']) ? implode(', ', $_POST['skills']) : '';
            $stmt = $pdo->prepare("
                UPDATE candidates SET skills = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            $stmt->execute([$skills, $userId]);
            
            setFlash('success', 'Skills updated successfully!');
            break;
            
        case 'experience':
            // Update experience as JSON
            $experience = [];
            if (isset($_POST['company_name']) && is_array($_POST['company_name'])) {
                foreach ($_POST['company_name'] as $index => $companyName) {
                    if (!empty($companyName)) {
                        $experience[] = [
                            'company_name' => $companyName,
                            'job_title' => $_POST['job_title'][$index] ?? '',
                            'employment_type' => $_POST['employment_type'][$index] ?? '',
                            'start_date' => $_POST['start_date'][$index] ?? '',
                            'end_date' => $_POST['end_date'][$index] ?? '',
                            'is_current' => isset($_POST['is_current'][$index]) ? 1 : 0
                        ];
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE candidates SET experience = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            $stmt->execute([json_encode($experience), $userId]);
            
            setFlash('success', 'Experience updated successfully!');
            break;
            
        case 'education':
            // Update education as JSON
            $education = [];
            if (isset($_POST['institution_name']) && is_array($_POST['institution_name'])) {
                foreach ($_POST['institution_name'] as $index => $institutionName) {
                    if (!empty($institutionName)) {
                        $education[] = [
                            'institution_name' => $institutionName,
                            'degree' => $_POST['degree'][$index] ?? '',
                            'year_of_passing' => $_POST['year_of_passing'][$index] ?? '',
                            'grade' => $_POST['grade'][$index] ?? ''
                        ];
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE candidates SET education = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            $stmt->execute([json_encode($education), $userId]);
            
            setFlash('success', 'Education updated successfully!');
            break;
    }
    
    // Refresh candidate data with user data
    $stmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name, u.email as user_email 
        FROM candidates c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.user_id = ? AND c.is_deleted = 0 AND u.is_deleted = 0
    ");
    $stmt->execute([$userId]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Parse JSON fields
$experience = !empty($candidate['experience']) ? json_decode($candidate['experience'], true) : [];
$education = !empty($candidate['education']) ? json_decode($candidate['education'], true) : [];
$skills = !empty($candidate['skills']) ? explode(', ', $candidate['skills']) : [];

// Calculate profile completion
$completion = calculateProfileCompletion($candidate);

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $uploadDir = '../assets/uploads/profile_photos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['profile_photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
        $filename = time() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $stmt = $pdo->prepare("
                UPDATE candidates SET profile_photo = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            $stmt->execute(['assets/uploads/profile_photos/' . $filename, $userId]);
            
            setFlash('success', 'Profile photo updated successfully!');
            
            // Refresh candidate data
            $stmt = $pdo->prepare("SELECT * FROM candidates WHERE user_id = ? AND is_deleted = 0");
            $stmt->execute([$userId]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            setFlash('error', 'Failed to upload profile photo');
        }
    } else {
        setFlash('error', 'Invalid file type or size. Please upload a JPEG, PNG, or GIF file under 5MB.');
    }
    
    redirect('/apex-nexus-portal/candidate/profile.php');
}
?>

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/candidate-modern.css">

<!-- Modern Candidate Navigation -->
<?php include '../includes/candidate-navbar.php'; ?>

<!-- Main Content Area -->
<div class="candidate-layout">
    <div class="layout-container">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <!-- Profile Photo -->
                <div class="text-center mb-6">
                    <div class="relative inline-block">
                        <div class="w-32 h-32 bg-gray-100 rounded-full mx-auto mb-4 overflow-hidden group">
                            <?php if (!empty($candidate['profile_photo'])): ?>
                                <img src="/apex-nexus-portal/<?php echo htmlspecialchars($candidate['profile_photo']); ?>" 
                                     alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="text-3xl font-medium text-gray-600">
                                        <?php echo substr($candidate['full_name'] ?? 'User', 0, 1); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Upload Overlay -->
                            <form method="POST" enctype="multipart/form-data" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                <input type="file" name="profile_photo" accept="image/*" class="hidden" id="profilePhotoInput">
                                <label for="profilePhotoInput" class="cursor-pointer text-white text-center">
                                    <svg class="w-8 h-8 mx-auto mb-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs">Change Photo</span>
                                </label>
                            </form>
                        </div>
                    </div>
                    
                    <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($candidate['full_name'] ?? 'User'); ?></h2>
                    <?php if (!empty($candidate['current_job_title'])): ?>
                        <p class="text-gray-600"><?php echo htmlspecialchars($candidate['current_job_title']); ?></p>
                        <?php if (!empty($candidate['current_company'])): ?>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($candidate['current_company']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Completion -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Profile Completion</span>
                        <span class="text-sm font-bold text-blue-600"><?php echo $completion; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $completion; ?>%"></div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Total Experience</span>
                        <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($candidate['total_experience'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Skills</span>
                        <span class="text-sm font-medium text-gray-800"><?php echo count($skills); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Applications</span>
                        <span class="text-sm font-medium text-gray-800">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE candidate_id = ? AND is_deleted = 0");
                            $stmt->execute([$candidateId]);
                            echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                        </span>
                    </div>
                </div>
                
                <!-- Download Resume -->
                <?php if (!empty($candidate['resume'])): ?>
                    <a href="/apex-nexus-portal/<?php echo htmlspecialchars($candidate['resume']); ?>" 
                       download class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-center block">
                        Download Resume
                    </a>
                <?php else: ?>
                    <a href="/apex-nexus-portal/candidate/upload-resume.php" 
                       class="w-full bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition-colors text-center block">
                        Upload Resume
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Edit Forms -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100">
                
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button onclick="showTab('personal')" id="tab-personal" 
                                class="tab-button active py-4 px-6 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            Personal Info
                        </button>
                        <button onclick="showTab('professional')" id="tab-professional" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Professional Info
                        </button>
                        <button onclick="showTab('skills')" id="tab-skills" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Skills
                        </button>
                        <button onclick="showTab('experience')" id="tab-experience" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Experience
                        </button>
                        <button onclick="showTab('education')" id="tab-education" 
                                class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Education
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div class="p-6">
                    
                    <!-- Personal Info Tab -->
                    <div id="content-personal" class="tab-content">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Personal Information</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="tab" value="personal">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($candidate['first_name'] ?? ''); ?>" 
                                           class="search-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($candidate['last_name'] ?? ''); ?>" 
                                           class="search-input" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($candidate['user_email'] ?? ''); ?>" 
                                       class="search-input" readonly>
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed here</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($candidate['phone'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($candidate['date_of_birth'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select name="gender" class="search-input">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($candidate['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($candidate['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($candidate['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                    <input type="text" name="city" value="<?php echo htmlspecialchars($candidate['city'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                    <input type="text" name="state" value="<?php echo htmlspecialchars($candidate['state'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                    <input type="text" name="country" value="<?php echo htmlspecialchars($candidate['country'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                                <input type="text" name="nationality" value="<?php echo htmlspecialchars($candidate['nationality'] ?? ''); ?>" 
                                       class="search-input">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">LinkedIn URL</label>
                                    <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($candidate['linkedin_url'] ?? ''); ?>" 
                                           class="search-input" placeholder="https://linkedin.com/in/...">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Portfolio URL</label>
                                    <input type="url" name="portfolio_url" value="<?php echo htmlspecialchars($candidate['portfolio_url'] ?? ''); ?>" 
                                           class="search-input" placeholder="https://...">
                                </div>
                            </div>
                            
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Save Personal Info
                            </button>
                        </form>
                    </div>
                    
                    <!-- Professional Info Tab -->
                    <div id="content-professional" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Professional Information</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="tab" value="professional">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Job Title</label>
                                    <input type="text" name="current_job_title" value="<?php echo htmlspecialchars($candidate['current_job_title'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Company</label>
                                    <input type="text" name="current_company" value="<?php echo htmlspecialchars($candidate['current_company'] ?? ''); ?>" 
                                           class="search-input">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Experience (years)</label>
                                    <input type="number" name="total_experience" value="<?php echo htmlspecialchars($candidate['total_experience'] ?? ''); ?>" 
                                           class="search-input" min="0" step="0.5">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notice Period</label>
                                    <select name="notice_period" class="search-input">
                                        <option value="">Select Notice Period</option>
                                        <option value="Immediate" <?php echo ($candidate['notice_period'] ?? '') === 'Immediate' ? 'selected' : ''; ?>>Immediate</option>
                                        <option value="15 days" <?php echo ($candidate['notice_period'] ?? '') === '15 days' ? 'selected' : ''; ?>>15 days</option>
                                        <option value="30 days" <?php echo ($candidate['notice_period'] ?? '') === '30 days' ? 'selected' : ''; ?>>30 days</option>
                                        <option value="60 days" <?php echo ($candidate['notice_period'] ?? '') === '60 days' ? 'selected' : ''; ?>>60 days</option>
                                        <option value="90 days" <?php echo ($candidate['notice_period'] ?? '') === '90 days' ? 'selected' : ''; ?>>90 days</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Job Type Preference</label>
                                <select name="job_type" class="search-input">
                                    <option value="">Select Job Type</option>
                                    <option value="Full-time" <?php echo ($candidate['job_type'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="Part-time" <?php echo ($candidate['job_type'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                    <option value="Contract" <?php echo ($candidate['job_type'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="Internship" <?php echo ($candidate['job_type'] ?? '') === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Location</label>
                                <input type="text" name="preferred_location" value="<?php echo htmlspecialchars($candidate['preferred_location'] ?? ''); ?>" 
                                       class="search-input" placeholder="Cities or regions you prefer">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Salary (<?php echo htmlspecialchars($currency ?? 'USD'); ?>)</label>
                                    <input type="number" name="current_salary" value="<?php echo htmlspecialchars($candidate['current_salary'] ?? ''); ?>" 
                                           class="search-input" min="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Salary (<?php echo htmlspecialchars($currency ?? 'USD'); ?>)</label>
                                    <input type="number" name="expected_salary" value="<?php echo htmlspecialchars($candidate['expected_salary'] ?? ''); ?>" 
                                           class="search-input" min="0">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Highest Qualification</label>
                                <select name="highest_qualification" class="search-input">
                                    <option value="">Select Qualification</option>
                                    <option value="High School" <?php echo ($candidate['highest_qualification'] ?? '') === 'High School' ? 'selected' : ''; ?>>High School</option>
                                    <option value="Diploma" <?php echo ($candidate['highest_qualification'] ?? '') === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                                    <option value="Bachelor's Degree" <?php echo ($candidate['highest_qualification'] ?? '') === "Bachelor's Degree" ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                    <option value="Master's Degree" <?php echo ($candidate['highest_qualification'] ?? '') === "Master's Degree" ? 'selected' : ''; ?>>Master's Degree</option>
                                    <option value="PhD" <?php echo ($candidate['highest_qualification'] ?? '') === 'PhD' ? 'selected' : ''; ?>>PhD</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Save Professional Info
                            </button>
                        </form>
                    </div>
                    
                    <!-- Skills Tab -->
                    <div id="content-skills" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Skills</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="tab" value="skills">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Your Skills</label>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php foreach ($skills as $skill): ?>
                                        <div class="skill-chip">
                                            <?php echo htmlspecialchars(trim($skill)); ?>
                                            <button type="button" onclick="removeSkill(this)">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="flex gap-2">
                                    <input type="text" id="newSkillInput" placeholder="Add a skill..." 
                                           class="search-input flex-1">
                                    <button type="button" onclick="addSkill()" 
                                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                        Add Skill
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Hidden input to store skills as array -->
                            <div id="skillsContainer" class="hidden">
                                <?php foreach ($skills as $index => $skill): ?>
                                    <input type="hidden" name="skills[]" value="<?php echo htmlspecialchars(trim($skill)); ?>">
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Save Skills
                            </button>
                        </form>
                    </div>
                    
                    <!-- Experience Tab -->
                    <div id="content-experience" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Work Experience</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="tab" value="experience">
                            
                            <div id="experienceContainer">
                                <?php foreach ($experience as $index => $exp): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 mb-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-medium text-gray-800">Experience <?php echo $index + 1; ?></h4>
                                            <button type="button" onclick="removeExperience(this)" 
                                                    class="text-red-600 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                                <input type="text" name="company_name[]" value="<?php echo htmlspecialchars($exp['company_name'] ?? ''); ?>" 
                                                       class="search-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                                                <input type="text" name="job_title[]" value="<?php echo htmlspecialchars($exp['job_title'] ?? ''); ?>" 
                                                       class="search-input">
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                                                <select name="employment_type[]" class="search-input">
                                                    <option value="">Select Type</option>
                                                    <option value="Full-time" <?php echo ($exp['employment_type'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                                    <option value="Part-time" <?php echo ($exp['employment_type'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                                    <option value="Contract" <?php echo ($exp['employment_type'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                                    <option value="Internship" <?php echo ($exp['employment_type'] ?? '') === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                                <input type="date" name="start_date[]" value="<?php echo htmlspecialchars($exp['start_date'] ?? ''); ?>" 
                                                       class="search-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                                <input type="date" name="end_date[]" value="<?php echo htmlspecialchars($exp['end_date'] ?? ''); ?>" 
                                                       class="search-input">
                                                <div class="mt-1">
                                                    <label class="flex items-center">
                                                        <input type="checkbox" name="is_current[]" value="1" 
                                                               <?php echo ($exp['is_current'] ?? 0) == 1 ? 'checked' : ''; ?>
                                                               class="mr-2">
                                                        <span class="text-sm text-gray-600">Currently working here</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" onclick="addExperience()" 
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                + Add Experience
                            </button>
                            
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Save Experience
                            </button>
                        </form>
                    </div>
                    
                    <!-- Education Tab -->
                    <div id="content-education" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Education</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="tab" value="education">
                            
                            <div id="educationContainer">
                                <?php foreach ($education as $index => $edu): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 mb-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-medium text-gray-800">Education <?php echo $index + 1; ?></h4>
                                            <button type="button" onclick="removeEducation(this)" 
                                                    class="text-red-600 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Institution Name</label>
                                                <input type="text" name="institution_name[]" value="<?php echo htmlspecialchars($edu['institution_name'] ?? ''); ?>" 
                                                       class="search-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Degree</label>
                                                <input type="text" name="degree[]" value="<?php echo htmlspecialchars($edu['degree'] ?? ''); ?>" 
                                                       class="search-input" placeholder="e.g., Bachelor of Computer Science">
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Year of Passing</label>
                                                <input type="number" name="year_of_passing[]" value="<?php echo htmlspecialchars($edu['year_of_passing'] ?? ''); ?>" 
                                                       class="search-input" min="1950" max="<?php echo date('Y'); ?>">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Grade/CGPA</label>
                                                <input type="text" name="grade[]" value="<?php echo htmlspecialchars($edu['grade'] ?? ''); ?>" 
                                                       class="search-input" placeholder="e.g., 8.5 CGPA or 75%">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" onclick="addEducation()" 
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                + Add Education
                            </button>
                            
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Save Education
                            </button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
        
    </div>

  </main>
</div>

<script>
// Tab switching
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

// Skills management
function addSkill() {
    const input = document.getElementById('newSkillInput');
    const skill = input.value.trim();
    
    if (skill) {
        const container = document.getElementById('skillsContainer');
        const skillChip = document.createElement('div');
        skillChip.className = 'skill-chip';
        skillChip.innerHTML = `
            ${skill}
            <button type="button" onclick="removeSkill(this)">×</button>
        `;
        
        // Add to display
        const displayContainer = container.previousElementSibling;
        displayContainer.appendChild(skillChip);
        
        // Add to hidden inputs
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'skills[]';
        hiddenInput.value = skill;
        container.appendChild(hiddenInput);
        
        input.value = '';
    }
}

function removeSkill(button) {
    const skillChip = button.parentElement;
    const skill = skillChip.textContent.replace('×', '').trim();
    
    // Remove from display
    skillChip.remove();
    
    // Remove from hidden inputs
    const container = document.getElementById('skillsContainer');
    const inputs = container.querySelectorAll('input[name="skills[]"]');
    inputs.forEach(input => {
        if (input.value === skill) {
            input.remove();
        }
    });
}

// Experience management
function addExperience() {
    const container = document.getElementById('experienceContainer');
    const index = container.children.length;
    
    const experienceDiv = document.createElement('div');
    experienceDiv.className = 'border border-gray-200 rounded-lg p-4 mb-4';
    experienceDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-medium text-gray-800">Experience ${index + 1}</h4>
            <button type="button" onclick="removeExperience(this)" 
                    class="text-red-600 hover:text-red-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                <input type="text" name="company_name[]" class="search-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                <input type="text" name="job_title[]" class="search-input">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                <select name="employment_type[]" class="search-input">
                    <option value="">Select Type</option>
                    <option value="Full-time">Full-time</option>
                    <option value="Part-time">Part-time</option>
                    <option value="Contract">Contract</option>
                    <option value="Internship">Internship</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date[]" class="search-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date[]" class="search-input">
                <div class="mt-1">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_current[]" value="1" class="mr-2">
                        <span class="text-sm text-gray-600">Currently working here</span>
                    </label>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(experienceDiv);
}

function removeExperience(button) {
    button.closest('.border').remove();
}

// Education management
function addEducation() {
    const container = document.getElementById('educationContainer');
    const index = container.children.length;
    
    const educationDiv = document.createElement('div');
    educationDiv.className = 'border border-gray-200 rounded-lg p-4 mb-4';
    educationDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-medium text-gray-800">Education ${index + 1}</h4>
            <button type="button" onclick="removeEducation(this)" 
                    class="text-red-600 hover:text-red-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Institution Name</label>
                <input type="text" name="institution_name[]" class="search-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Degree</label>
                <input type="text" name="degree[]" class="search-input" placeholder="e.g., Bachelor of Computer Science">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year of Passing</label>
                <input type="number" name="year_of_passing[]" class="search-input" min="1950" max="${new Date().getFullYear()}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grade/CGPA</label>
                <input type="text" name="grade[]" class="search-input" placeholder="e.g., 8.5 CGPA or 75%">
            </div>
        </div>
    `;
    
    container.appendChild(educationDiv);
}

function removeEducation(button) {
    button.closest('.border').remove();
}

// Handle Enter key for skills input
document.addEventListener('DOMContentLoaded', function() {
    const skillInput = document.getElementById('newSkillInput');
    if (skillInput) {
        skillInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSkill();
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>