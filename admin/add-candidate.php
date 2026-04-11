<?php


/**
 * Admin Add Candidate Page
 */

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Check if we're in edit mode
$isEdit = isset($_GET['id']);
$candidateId = $isEdit ? (int) $_GET['id'] : 0;
$candidate = null;

$pageTitle = $isEdit ? "Edit Candidate - Admin" : "Add Candidate - Admin";

// If editing, fetch existing candidate data
if ($isEdit && $candidateId > 0) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $stmt = $pdo->prepare("
    SELECT c.*, 
           u.first_name, 
           u.last_name, 
           u.email,
           CONCAT(u.first_name, ' ', u.last_name) as user_name
    FROM candidates c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.id = ? AND c.is_deleted = 0
");
        $stmt->execute([$candidateId]);
        $candidate = $stmt->fetch();

        if (!$candidate) {
            setFlash('error', 'Candidate not found');
            redirect('admin/candidates.php');
        }
    } catch (PDOException $e) {
        error_log("Candidate fetch error: " . $e->getMessage());
        setFlash('error', 'Failed to load candidate data');
        redirect('admin/candidates.php');
    }
}

// File upload helper function
function handleFileUpload($file, $subfolder = 'uploads')
{
    // Create upload directory if it doesn't exist
    $uploadDir = '../assets/uploads/' . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    // Validate file type and size
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'application/zip'
    ];

    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only PDF, DOC, DOCX, JPG, PNG, and ZIP files are allowed.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'assets/uploads/' . $subfolder . '/' . $fileName;
    }

    throw new Exception('Failed to upload file.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    // Debug: Print all data to console
    echo "<script>";
    echo "console.log('=== FORM SUBMISSION DEBUG ===');";
    echo "console.log('POST Data:', " . json_encode($_POST) . ");";
    echo "console.log('FILES Data:', " . json_encode($_FILES) . ");";
    echo "console.log('Experience Companies:', " . json_encode($_POST['exp_company'] ?? []) . ");";
    echo "console.log('Experience Job Titles:', " . json_encode($_POST['exp_job_title'] ?? []) . ");";
    echo "console.log('Experience Start Dates:', " . json_encode($_POST['exp_start_date'] ?? []) . ");";
    echo "console.log('Experience End Dates:', " . json_encode($_POST['exp_end_date'] ?? []) . ");";
    echo "console.log('Experience Current:', " . json_encode($_POST['exp_current'] ?? []) . ");";
    echo "console.log('Experience Types:', " . json_encode($_POST['exp_employment_type'] ?? []) . ");";
    echo "console.log('Experience Descriptions:', " . json_encode($_POST['exp_description'] ?? []) . ");";
    echo "console.log('Skills:', " . json_encode($_POST['skills'] ?? '') . ");";
    echo "</script>";

    $firstName = clean($_POST['first_name'] ?? '');
    $lastName = clean($_POST['last_name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $city = clean($_POST['city'] ?? '');
    $state = clean($_POST['state'] ?? '');
    $country = clean($_POST['country'] ?? '');
    $currentJobTitle = clean($_POST['current_job_title'] ?? '');
    $currentCompany = clean($_POST['current_company'] ?? '');
    $totalExperience = clean($_POST['total_experience'] ?? '');
    $currentSalary = clean($_POST['current_salary'] ?? '');
    $expectedSalary = clean($_POST['expected_salary'] ?? '');
    $preferredLocation = clean($_POST['preferred_location'] ?? '');
    $jobType = clean($_POST['job_type'] ?? '');
    $noticePeriod = clean($_POST['notice_period'] ?? '');
    $linkedinUrl = clean($_POST['linkedin_url'] ?? '');
    $portfolioUrl = clean($_POST['portfolio_url'] ?? '');
    $dateOfBirth = clean($_POST['date_of_birth'] ?? '');
    $gender = clean($_POST['gender'] ?? '');
    $skills = clean($_POST['skills'] ?? '');
    $highestQualification = clean($_POST['highest_qualification'] ?? '');
    $education = clean($_POST['education'] ?? '');
    $nationality = clean($_POST['nationality'] ?? '');

    // Handle file uploads
    $resumeFile = '';


    // Handle resume upload
    $resumeFile = $isEdit ? ($candidate['resume'] ?? '') : ''; // keep existing in edit mode

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $resumeFile = handleFileUpload($_FILES['resume'], 'resumes'); // overwrite only if new file uploaded
    }

    // Handle cover letter upload






    // Handle multiple experiences
    $experiences = [];
    if (isset($_POST['exp_company']) && is_array($_POST['exp_company'])) {
        $count = count($_POST['exp_company']);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($_POST['exp_company'][$i])) {
                $experiences[] = [
                    'company' => clean($_POST['exp_company'][$i]),
                    'job_title' => clean($_POST['exp_job_title'][$i] ?? ''),
                    'start_date' => clean($_POST['exp_start_date'][$i] ?? ''),
                    'end_date' => clean($_POST['exp_end_date'][$i] ?? ''),
                    'is_current' => isset($_POST['exp_current'][$i]) ? 1 : 0,
                    // employement_type
                    'employment_type' => isset($_POST['exp_employment_type'][$i]) ? clean($_POST['exp_employment_type'][$i]) : '',
                ];
            }
        }
    }

    // Convert experiences array to JSON for storage
    $experienceJson = json_encode($experiences);

    // Validate inputs
    $errors = [];
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Invalid email format';
    }

    // Validate resume upload
    if (empty($resumeFile) && !$isEdit) {
        $errors[] = 'Resume is required';
    }

    if (empty($errors)) {

        try {
            $database = new Database();
            $pdo = $database->getConnection();
            $pdo->beginTransaction();

            if ($isEdit) {

                // UPDATE users
                $userStmt = $pdo->prepare("
                UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?, updated_at = NOW() 
                WHERE id = ?
            ");
                $userStmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    $candidate['user_id']
                ]);

                // UPDATE candidates
                $candidateStmt = $pdo->prepare("
                UPDATE candidates SET 
                    skills = ?, experience = ?, education = ?, full_name = ?,
                    email = ?, phone = ?, city = ?, state = ?, country = ?,
                    current_job_title = ?, current_company = ?, total_experience = ?,
                    current_salary = ?, expected_salary = ?, preferred_location = ?,
                    job_type = ?, notice_period = ?, linkedin_url = ?, portfolio_url = ?,
                    date_of_birth = ?, gender = ?, highest_qualification = ?, nationality = ?, updated_at = NOW()
                WHERE id = ?
            ");
                $candidateStmt->execute([
                    $skills,
                    $experienceJson,
                    $education,
                    ($firstName . ' ' . $lastName),
                    $email,
                    $phone,
                    $city,
                    $state,
                    $country,
                    $currentJobTitle,
                    $currentCompany,
                    $totalExperience,
                    $currentSalary,
                    $expectedSalary,
                    $preferredLocation,
                    $jobType,
                    $noticePeriod,
                    $linkedinUrl,
                    $portfolioUrl,
                    $dateOfBirth,
                    $gender,
                    $highestQualification,
                    $nationality,



                    $candidateId
                ]);

                // DELETE old experiences then re-insert
                $pdo->prepare("DELETE FROM candidate_experience WHERE candidate_id = ?")
                    ->execute([$candidateId]);

                if (!empty($experiences)) {
                    $expStmt = $pdo->prepare("
                    INSERT INTO candidate_experience (
                        candidate_id, company_name, job_title, start_date, end_date,
                        is_current, employment_type, added_by, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                    foreach ($experiences as $exp) {
                        $expStmt->execute([
                            $candidateId,
                            $exp['company'],
                            $exp['job_title'],
                            $exp['start_date'],
                            $exp['end_date'],
                            $exp['is_current'],
                            $exp['employment_type'] ?? '',
                            $_SESSION['user_id']
                        ]);
                    }
                }

                $successMessage = 'Candidate updated successfully!';

            } else {

                // INSERT users
                $hashedPassword = password_hash('default123', PASSWORD_DEFAULT);
                $userStmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, is_deleted, added_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'candidate', 0, ?, NOW(), NOW())
            ");
                $userStmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    $hashedPassword,
                    $_SESSION['user_id']
                ]);
                $userId = $pdo->lastInsertId();

                // INSERT candidates
                // Total columns: 28 (id auto, created_at/updated_at = NOW(), is_deleted = 0 hardcoded)
                // Total ? marks: 25
                $candidateStmt = $pdo->prepare("
                INSERT INTO candidates (
                    user_id, resume, skills, experience, education,
                    full_name, email, phone, city, state, country,
                    current_job_title, current_company, total_experience,
                    current_salary, expected_salary, preferred_location,
                    job_type, notice_period, linkedin_url, portfolio_url,
                    profile_photo, date_of_birth, gender, highest_qualification,
                    is_deleted, added_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    0, ?, NOW(), NOW()
                )
            ");


                $candidateStmt->execute([
                    $userId,            // user_id
                    $resumeFile,        // resume
                    $skills,            // skills
                    $experienceJson,    // experience
                    $education,         // education
                    ($firstName . ' ' . $lastName), // full_name
                    $email,             // email
                    $phone,             // phone
                    $city,              // city
                    $state,             // state
                    $country,           // country
                    $currentJobTitle,   // current_job_title
                    $currentCompany,    // current_company
                    $totalExperience,   // total_experience
                    $currentSalary,     // current_salary
                    $expectedSalary,    // expected_salary
                    $preferredLocation, // preferred_location
                    $jobType,           // job_type
                    $noticePeriod,      // notice_period
                    $linkedinUrl,       // linkedin_url
                    $portfolioUrl,      // portfolio_url
                    '',                 // profile_photo
                    $dateOfBirth,       // date_of_birth
                    $gender,            // gender
                    $highestQualification, // highest_qualification
                    $nationality,       // nationality
                    // is_deleted = 0 hardcoded
                    $_SESSION['user_id'] // added_by
                ]);
                $candidateId = $pdo->lastInsertId();

                // INSERT experiences
                if (!empty($experiences)) {
                    $expStmt = $pdo->prepare("
                    INSERT INTO candidate_experience (
                        candidate_id, company_name, job_title, start_date, end_date,
                        is_current, employment_type, added_by, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                    foreach ($experiences as $exp) {
                        $expStmt->execute([
                            $candidateId,
                            $exp['company'],
                            $exp['job_title'],
                            $exp['start_date'],
                            $exp['end_date'],
                            $exp['is_current'],
                            $exp['employment_type'] ?? '',
                            $_SESSION['user_id']
                        ]);
                    }
                }

                $successMessage = 'Candidate added successfully! Default password: default123';
            }

            $pdo->commit();
            setFlash('success', $successMessage);
            redirect('admin/candidates.php');

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            die("<pre style='background:#fee2e2;padding:20px;color:#991b1b;'>
DB ERROR  : " . $e->getMessage() . "
Error Code: " . $e->getCode() . "
Line      : " . $e->getLine() . "
        </pre>");
        }

    } else {
        setFlash('error', implode(', ', $errors));
    }
}

// Include navbar and sidebar
// require_once '../includes/navbar.php';
require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div
        class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">
                <?php echo $isEdit ? 'Edit Candidate' : 'Add New Candidate'; ?>
            </h1>
            <p class="text-sm text-gray-500">
                <?php echo $isEdit ? 'Update candidate information' : 'Register a new candidate in system'; ?>
            </p>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-4">
            <!-- Notification -->
            <button class="relative p-2 rounded-full hover:bg-gray-100 transition">
                🔔
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- Profile -->
            <div
                class="flex items-center gap-3 bg-gray-100 px-3 py-2 rounded-full cursor-pointer hover:bg-gray-200 transition">
                <!-- Avatar -->
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <!-- Name -->
                <span class="text-sm font-medium text-gray-700 hidden sm:block">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="p-6">
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="add_candidate" value="1">

                        <!-- Personal Information Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Personal Information
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- First Name -->
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="first_name" name="first_name" required
                                        value="<?php echo htmlspecialchars($candidate['first_name'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter candidate first name">
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="last_name" name="last_name" required
                                        value="<?php echo htmlspecialchars($candidate['last_name'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter candidate last name">
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="candidate@example.com"
                                        value="<?php echo htmlspecialchars($candidate['email'] ?? ''); ?>">
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" id="phone" name="phone"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="+1 234 567 8900"
                                        value="<?php echo htmlspecialchars($candidate['phone'] ?? ''); ?>">
                                </div>

                                <!-- City -->
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input type="text" id="city" name="city"
                                        value="<?php echo htmlspecialchars($candidate['city'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter city">
                                </div>

                                <!-- State -->
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State
                                    </label>
                                    <input type="text" id="state" name="state"
                                        value="<?php echo htmlspecialchars($candidate['state'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter state">
                                </div>

                                <!-- Country -->
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country
                                    </label>
                                    <input type="text" id="country" name="country"
                                        value="<?php echo htmlspecialchars($candidate['country'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter country">
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                                        Date of Birth
                                    </label>
                                    <input type="date" id="date_of_birth" name="date_of_birth"
                                        value="<?php echo htmlspecialchars($candidate['date_of_birth'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Gender -->
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                                        Gender
                                    </label>
                                    <select id="gender" name="gender"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($candidate['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($candidate['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($candidate['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <!-- nationality -->
                                <div>
                                    <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nationality
                                    </label>
                                    <input type="text" id="nationality" name="nationality"
                                        value="<?php echo htmlspecialchars($candidate['nationality'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter nationality">
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Professional Information
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Current Job Title -->

                                <!-- highest qualification dropdown -->
                                <div>
                                    <label for="highest_qualification"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Highest Qualification
                                    </label>
                                    <select id="highest_qualification" name="highest_qualification"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select qualification</option>
                                        <option value="BE/Btech" <?php echo ($candidate['highest_qualification'] ?? '') === 'BE/Btech' ? 'selected' : ''; ?>>BE / Btech</option>
                                        <option value="ME/Mtech" <?php echo ($candidate['highest_qualification'] ?? '') === 'ME/Mtech' ? 'selected' : ''; ?>>ME / Mtech</option>
                                        <option value="PhD" <?php echo ($candidate['highest_qualification'] ?? '') === 'PhD' ? 'selected' : ''; ?>>PhD</option>
                                        <option value="BCA/MCA" <?php echo ($candidate['highest_qualification'] ?? '') === 'BCA/MCA' ? 'selected' : ''; ?>>BCA / MCA</option>
                                        <option value="MBA" <?php echo ($candidate['highest_qualification'] ?? '') === 'MBA' ? 'selected' : ''; ?>>MBA</option>
                                        <option value="Diploma" <?php echo ($candidate['highest_qualification'] ?? '') === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                                        <option value="Other" <?php echo ($candidate['highest_qualification'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>




                                <!-- Total Experience -->
                                <div>
                                    <label for="total_experience" class="block text-sm font-medium text-gray-700 mb-2">
                                        Total Experience (years)
                                    </label>
                                    <input type="number" id="total_experience" name="total_experience" step="0.1"
                                        value="<?php echo htmlspecialchars($candidate['total_experience'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., 5.5">
                                </div>

                                <!-- Current Salary -->
                                <div>
                                    <label for="current_salary" class="block text-sm font-medium text-gray-700 mb-2">
                                        Current Salary
                                    </label>
                                    <input type="text" id="current_salary" name="current_salary"
                                        value="<?php echo htmlspecialchars($candidate['current_salary'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., $80,000">
                                </div>

                                <!-- Expected Salary -->
                                <div>
                                    <label for="expected_salary" class="block text-sm font-medium text-gray-700 mb-2">
                                        Expected Salary
                                    </label>
                                    <input type="text" id="expected_salary" name="expected_salary"
                                        value="<?php echo htmlspecialchars($candidate['expected_salary'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., $90,000">
                                </div>

                                <!-- Preferred Location -->
                                <div>
                                    <label for="preferred_location"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Preferred Location
                                    </label>
                                    <input type="text" id="preferred_location" name="preferred_location"
                                        value="<?php echo htmlspecialchars($candidate['preferred_location'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., New York, Remote">
                                </div>

                                <!-- Job Type -->
                                <div>
                                    <label for="job_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        Job Type Preference
                                    </label>
                                    <select id="job_type" name="job_type"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Job Type</option>
                                        <option value="Full-time" <?php echo ($candidate['job_type'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                        <option value="Part-time" <?php echo ($candidate['job_type'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                        <option value="Contract" <?php echo ($candidate['job_type'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="Freelance" <?php echo ($candidate['job_type'] ?? '') === 'Freelance' ? 'selected' : ''; ?>>Freelance</option>
                                        <option value="Internship" <?php echo ($candidate['job_type'] ?? '') === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>

                                <!-- Notice Period -->
                                <div>
                                    <label for="notice_period" class="block text-sm font-medium text-gray-700 mb-2">
                                        Notice Period
                                    </label>
                                    <input type="text" id="notice_period" name="notice_period"
                                        value="<?php echo htmlspecialchars($candidate['notice_period'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., 30 days, Immediate">
                                </div>

                                <!-- LinkedIn URL -->
                                <div>
                                    <label for="linkedin_url" class="block text-sm font-medium text-gray-700 mb-2">
                                        LinkedIn URL
                                    </label>
                                    <input type="url" id="linkedin_url" name="linkedin_url"
                                        value="<?php echo htmlspecialchars($candidate['linkedin_url'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://linkedin.com/in/username">
                                </div>

                                <!-- Portfolio URL -->
                                <div>
                                    <label for="portfolio_url" class="block text-sm font-medium text-gray-700 mb-2">
                                        Portfolio URL
                                    </label>
                                    <input type="url" id="portfolio_url" name="portfolio_url"
                                        value="<?php echo htmlspecialchars($candidate['portfolio_url'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://portfolio.example.com">
                                </div>

                                <div>
                                    <label for="skills" class="block text-sm font-medium text-gray-700 mb-2">
                                        Skills
                                    </label>

                                    <!-- Skills Input Container -->
                                    <div class="space-y-3">
                                        <!-- Skills Input -->
                                        <div class="flex items-center gap-2">
                                            <input type="text" id="skillInput"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Type a skill and press Enter"
                                                onkeydown="handleSkillInput(event)">
                                            <button type="button" onclick="addSkillFromInput()"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                                Add
                                            </button>
                                        </div>

                                        <!-- Skills Display -->
                                        <div id="skillsDisplay" class="flex flex-wrap gap-2">
                                            <!-- Skills will be displayed here as blue boxes -->
                                        </div>

                                        <!-- Hidden Skills Field -->
                                        <input type="hidden" id="skills" name="skills"
                                            value="<?php echo htmlspecialchars($candidate['skills'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 mt-6">
                                <!-- Skills -->


                                <!-- Multiple Work Experiences -->
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Work Experience
                                        </label>
                                        <button type="button" onclick="addExperienceField()"
                                            class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Add Experience
                                        </button>
                                    </div>

                                    <!-- Experience Container -->
                                    <div id="experienceContainer" class="space-y-4">
                                        <!-- First Experience Entry (Always Visible) -->
                                        <div class="experience-entry border border-gray-200 rounded-lg p-4 bg-gray-50">
                                            <div class="flex justify-between items-start mb-3">
                                                <h4 class="text-sm font-medium text-gray-700">Experience 1</h4>
                                                <button type="button" onclick="removeExperienceField(this)"
                                                    class="text-red-500 hover:text-red-700 text-sm"
                                                    style="display: none;">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <!-- Company Name -->
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                        Company Name
                                                    </label>
                                                    <input type="text" name="exp_company[]"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="e.g., Tech Corp">
                                                </div>

                                                <!-- Job Title -->
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                        Job Title
                                                    </label>
                                                    <input type="text" name="exp_job_title[]"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="e.g., Senior Developer">
                                                </div>

                                                <!-- Start Date -->
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                        Start Date
                                                    </label>
                                                    <input type="date" name="exp_start_date[]"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                                </div>

                                                <!-- End Date -->
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                        End Date
                                                    </label>
                                                    <div class="flex items-center gap-2">
                                                        <input type="date" name="exp_end_date[]"
                                                            class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                                        <label class="flex items-center text-xs">
                                                            <input type="checkbox" name="exp_current[]" value="1"
                                                                class="mr-1" onchange="toggleCurrentDate(this)">
                                                            Current
                                                        </label>
                                                    </div>
                                                </div>

                                                <!-- employement type  -->
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                        Employment Type
                                                    </label>
                                                    <select name="exp_employment_type[]"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                                        <option value="">Select Type</option>
                                                        <option value="Full-time">Full-time</option>
                                                        <option value="Part-time">Part-time</option>
                                                        <option value="Contract">Contract</option>
                                                        <option value="Freelance">Freelance</option>
                                                        <option value="Internship">Internship</option>
                                                    </select>
                                                </div>



                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Education -->

                            </div>
                        </div>

                        <!-- Resume and Documents -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resume and Documents</h3>

                            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                                <!-- Resume Upload -->
                                <div>
                                    <label for="resume" class="block text-sm font-medium text-gray-700 mb-2">
                                        Resume <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors duration-200 bg-gray-50 hover:bg-blue-50">
                                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                onchange="updateFileNameDisplay(this, 'resumeFileName')" <?php echo $isEdit ? '' : 'required'; ?>>
                                            <div id="resumeFileName" class="text-sm text-gray-600">
                                                <?php if ($isEdit && !empty($candidate['resume'])): ?>
                                                    <p class="font-medium text-green-700 mb-1">
                                                        ✓ Current:
                                                        <?php echo htmlspecialchars(basename($candidate['resume'])); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">Upload a new file to replace it, or
                                                        leave empty to keep current</p>
                                                <?php else: ?>
                                                    <p class="font-medium text-gray-900 mb-1">Click to upload or drag and
                                                        drop</p>
                                                    <p class="text-xs text-gray-500">PDF, DOC, DOCX up to 5MB</p>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" onclick="clearResumeFile()"
                                                class="absolute top-3 right-3 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                title="Clear file">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>


                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-center gap-4 pt-4">
                            <button type="submit"
                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg shadow hover:scale-105 transition font-semibold text-base flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                                    </path>
                                </svg>
                                <?php echo $isEdit ? 'Update Candidate' : 'Add Candidate'; ?>
                            </button>

                            <a href="/apex-nexus-portal/admin/candidates.php"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold text-base">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">
                            <?php echo $isEdit ? 'Update Information' : 'Important Information'; ?>
                        </h3>
                        <div class="mt-2 text-sm text-green-700">
                            <?php if ($isEdit): ?>
                                <p>• Updating candidate information will modify the existing record</p>
                                <p>• User account details will also be updated</p>
                                <p>• Changes will be reflected immediately</p>
                            <?php else: ?>
                                <p>• A user account will be automatically created for the candidate</p>
                                <p>• Default password: <code class="bg-green-100 px-1 rounded">default123</code></p>
                                <p>• The candidate can change their password after first login</p>
                                <p>• They will be able to apply for jobs and upload their resume</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Dynamic Experience Field Management
    let experienceCount = 1;

    function addExperienceField() {
        experienceCount++;
        const container = document.getElementById('experienceContainer');

        const experienceEntry = document.createElement('div');
        experienceEntry.className = 'experience-entry border border-gray-200 rounded-lg p-4 bg-gray-50';
        experienceEntry.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <h4 class="text-sm font-medium text-gray-700">Experience ${experienceCount}</h4>
            <button 
                type="button" 
                onclick="removeExperienceField(this)"
                class="text-red-500 hover:text-red-700 text-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Company Name -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Company Name
                </label>
                <input 
                    type="text" 
                    name="exp_company[]" 
                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., Tech Corp"
                >
            </div>
            
            <!-- Job Title -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Job Title
                </label>
                <input 
                    type="text" 
                    name="exp_job_title[]" 
                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., Senior Developer"
                >
            </div>
            
            <!-- Start Date -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Start Date
                </label>
                <input 
                    type="date" 
                    name="exp_start_date[]" 
                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            
            <!-- End Date -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    End Date
                </label>
                <div class="flex items-center gap-2">
                    <input 
                        type="date" 
                        name="exp_end_date[]" 
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                    <label class="flex items-center text-xs">
                        <input 
                            type="checkbox" 
                            name="exp_current[]" 
                            value="1" 
                            class="mr-1"
                            onchange="toggleCurrentDate(this)"
                        >
                        Current
                    </label>
                </div>
            </div>
 <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Employment Type
                        </label>
                        <select 
                            name="exp_employment_type[]" 
                            class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select type</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Freelance">Freelance</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>


         
            

            
            
        </div>
    `;

        container.appendChild(experienceEntry);

        // Show remove button for first entry if there are now multiple entries
        const firstRemoveBtn = container.querySelector('.experience-entry:first-child button');
        if (firstRemoveBtn) {
            firstRemoveBtn.style.display = 'block';
        }
    }

    function removeExperienceField(button) {
        const entry = button.closest('.experience-entry');
        const container = document.getElementById('experienceContainer');

        entry.remove();

        // Hide remove button for first entry if there's only one left
        const remainingEntries = container.querySelectorAll('.experience-entry');
        if (remainingEntries.length === 1) {
            const firstRemoveBtn = remainingEntries[0].querySelector('button');
            if (firstRemoveBtn) {
                firstRemoveBtn.style.display = 'none';
            }
        }

        // Renumber remaining entries
        renumberExperienceEntries();
    }

    function renumberExperienceEntries() {
        const entries = document.querySelectorAll('.experience-entry');
        entries.forEach((entry, index) => {
            const title = entry.querySelector('h4');
            if (title) {
                title.textContent = `Experience ${index + 1}`;
            }
        });
        experienceCount = entries.length;
    }

    function toggleCurrentDate(checkbox) {
        const wrapper = checkbox.closest('div.flex');
        const endDateInput = wrapper ? wrapper.querySelector('input[type="month"]') : null;
        if (!endDateInput) return;

        if (checkbox.checked) {
            endDateInput.value = '';
            endDateInput.readOnly = true;    // ← use readOnly instead of disabled
            endDateInput.style.opacity = '0.5';
        } else {
            endDateInput.readOnly = false;
            endDateInput.style.opacity = '1';
        }
    }

    // Skills Management
    let skills = [];

    function handleSkillInput(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addSkillFromInput();
        }
    }

    function addSkillFromInput() {
        const input = document.getElementById('skillInput');
        const skill = input.value.trim();

        if (skill && !skills.includes(skill)) {
            skills.push(skill);
            updateSkillsDisplay();
            input.value = '';
        }
    }

    function removeSkill(skill) {
        skills = skills.filter(s => s !== skill);
        updateSkillsDisplay();
    }

    function updateSkillsDisplay() {
        const display = document.getElementById('skillsDisplay');
        const hiddenField = document.getElementById('skills');

        display.innerHTML = skills.map(skill => `
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200">
            ${skill}
            <button 
                type="button" 
                onclick="removeSkill('${skill}')"
                class="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </span>
    `).join('');

        hiddenField.value = skills.join(', ');
    }

    function updateFileNameDisplay(input, displayId) {
        const display = document.getElementById(displayId);
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            if (fileName.length > 25) {
                display.textContent = fileName.substring(0, 22) + '...';
            } else {
                display.textContent = fileName;
            }
            display.classList.add('text-blue-600', 'font-medium');
            display.classList.remove('text-gray-600');
        } else {
            display.textContent = 'Choose file or drag here';
            display.classList.remove('text-blue-600', 'font-medium');
            display.classList.add('text-gray-600');
        }
    }

    function clearResumeFile() {
        const resumeInput = document.getElementById('resume');
        const resumeFileName = document.getElementById('resumeFileName');

        // Clear the file input
        resumeInput.value = '';

        // Reset the display text
        resumeFileName.innerHTML = `
        <p class="font-medium text-gray-900 mb-1">Click to upload or drag and drop</p>
        <p class="text-xs text-gray-500">PDF, DOC, DOCX up to 5MB</p>
    `;

        // Show clear button animation
        const button = event.target;
        button.style.transform = 'scale(0.9)';
        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 100);
    }

    // Load existing experiences when editing
    <?php if ($isEdit && !empty($candidate['experience'])): ?>
        document.addEventListener('DOMContentLoaded', function () {
            const experiences = <?php echo json_decode($candidate['experience']) ? json_encode(json_decode($candidate['experience'])) : '[]'; ?>;

            // Load existing skills
            const existingSkills = '<?php echo htmlspecialchars($candidate['skills'] ?? ''); ?>';
            if (existingSkills) {
                skills = existingSkills.split(',').map(s => s.trim()).filter(s => s);
                updateSkillsDisplay();
            }

            if (experiences && experiences.length > 0) {
                // Clear the first empty experience entry
                const container = document.getElementById('experienceContainer');
                container.innerHTML = '';

                // Load each experience
                experiences.forEach(function (exp, index) {
                    const experienceEntry = document.createElement('div');
                    experienceEntry.className = 'experience-entry border border-gray-200 rounded-lg p-4 bg-gray-50';
                    experienceEntry.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Experience ${index + 1}</h4>
                    <button 
                        type="button" 
                        onclick="removeExperienceField(this)"
                        class="text-red-500 hover:text-red-700 text-sm"
                        ${index === 0 && experiences.length === 1 ? 'style="display: none;"' : ''}
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Company Name -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Company Name
                        </label>
                        <input 
                            type="text" 
                            name="exp_company[]" 
                            class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., Tech Corp"
                            value="${exp.company || ''}"
                        >
                    </div>
                    
                    <!-- Job Title -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Job Title
                        </label>
                        <input 
                            type="text" 
                            name="exp_job_title[]" 
                            class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., Senior Developer"
                            value="${exp.job_title || ''}"
                        >
                    </div>
                    
                    <!-- Start Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Start Date
                        </label>
                        <input 
                            type="date" 
                            name="exp_start_date[]" 
                            class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                            value="${exp.start_date || ''}"
                        >
                    </div>
                    
                    <!-- End Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            End Date
                        </label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="date" 
                                name="exp_end_date[]" 
                                class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                                value="${exp.end_date || ''}"
                                ${exp.is_current ? 'disabled' : ''}
                            >
                            <label class="flex items-center text-xs">
                                <input 
                                    type="checkbox" 
                                    name="exp_current[]" 
                                    value="1" 
                                    class="mr-1"
                                    onchange="toggleCurrentDate(this)"
                                    ${exp.is_current ? 'checked' : ''}
                                >
                                Current
                            </label>
                        </div>
                    </div>
                    
                    <!-- employement type -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Employment Type
                        </label>
                        <select 
                            name="exp_employment_type[]" 
                            class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select type</option>
                            <option value="Full-time" ${exp.employment_type === 'Full-time' ? 'selected' : ''}>Full-time</option>
                            <option value="Part-time" ${exp.employment_type === 'Part-time' ? 'selected' : ''}>Part-time</option>
                            <option value="Contract" ${exp.employment_type === 'Contract' ? 'selected' : ''}>Contract</option>
                            <option value="Freelance" ${exp.employment_type === 'Freelance' ? 'selected' : ''}>Freelance</option>
                            <option value="Internship" ${exp.employment_type === 'Internship' ? 'selected' : ''}>Internship</option>
                        </select>
                    </div>
                    
                </div>
            `;

                    container.appendChild(experienceEntry);
                });

                experienceCount = experiences.length;
            }
        });
    <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>