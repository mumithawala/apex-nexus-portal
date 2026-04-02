<?php
/**
 * Admin Add Job Page
 */

// Debug: Show request method and POST data at the very top
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . print_r($_POST, true));
}

$pageTitle = "Add Job - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Check if we're editing an existing job
$editing = false;
$job_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$job_data = [];


if ($job_id > 0) {
    $editing = true;
    $pageTitle = "Edit Job - Admin";

    // Fetch existing job data
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM jobs 
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$job_id]);
        $job_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job_data) {
            setFlash('error', 'Job not found');
            redirect('/apex-nexus-portal/admin/jobs.php');
        }
    } catch (PDOException $e) {
        error_log("Job fetch error: " . $e->getMessage());
        setFlash('error', 'Failed to load job data');
        redirect('/apex-nexus-portal/admin/jobs.php');
    }
}

// Fetch companies for dropdown
try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name FROM companies c LEFT JOIN users u ON c.added_by = u.id WHERE c.is_deleted = 0 ORDER BY c.company_name");
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Companies fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load companies data');
    $companies = [];
}

// Fetch departments for dropdown
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM job_departments WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Departments fetch error: " . $e->getMessage());
    $departments = [];
}

// Fetch categories for dropdown
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM job_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

// Define work modes and employment types
$work_modes = [
    'remote' => 'Remote',
    'on-site' => 'On-site',
    'hybrid' => 'Hybrid'
];

$employment_types = [
    'full-time' => 'Full-time',
    'part-time' => 'Part-time',
    'contract' => 'Contract',
    'freelance' => 'Freelance',
    'internship' => 'Internship'
];

$education_levels = [
    'High School' => 'High School',
    'Associate' => 'Associate Degree',
    'Bachelor' => 'Bachelor\'s Degree',
    'Master' => 'Master\'s Degree',
    'PhD' => 'PhD',
    'Other' => 'Other'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    // Debug: Check if we're entering the form handler
    error_log("Form submission detected - POST data: " . print_r($_POST, true));

    // Sanitize inputs
    $company_id = (int) ($_POST['company_id'] ?? 0);
    $department_id = (int) ($_POST['department_id'] ?? 0);
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $title = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $responsibilities = clean($_POST['responsibilities'] ?? '');
    $requirements = clean($_POST['requirements'] ?? '');
    $nice_to_have = clean($_POST['nice_to_have'] ?? '');
    $perks = clean($_POST['perks'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $city = clean($_POST['city'] ?? '');
    $state = clean($_POST['state'] ?? '');
    $country = clean($_POST['country'] ?? '');
    $work_mode = clean($_POST['work_mode'] ?? '');
    $employment_type = clean($_POST['employment_type'] ?? '');
    $salary = clean($_POST['salary'] ?? '');
    $salary_min = clean($_POST['salary_min'] ?? '');
    $salary_max = clean($_POST['salary_max'] ?? '');
    $salary_visible = isset($_POST['salary_visible']) ? 1 : 0;
    $experience_required = clean($_POST['experience_required'] ?? '');
    $education = clean($_POST['education'] ?? '');
    $openings = (int) ($_POST['openings'] ?? 1);
    $deadline = clean($_POST['deadline'] ?? '');
    $status = clean($_POST['status'] ?? 'active');

    // Validate required fields
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Job title is required';
    }
    if (empty($description)) {
        $errors[] = 'Job description is required';
    }
    if (empty($location)) {
        $errors[] = 'Location is required';
    }
    if ($company_id <= 0) {
        $errors[] = 'Please select a company';
    }

    // Validate company exists
    if ($company_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$company_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Selected company is not valid';
        }
    }

    // Validate deadline if provided
    if (!empty($deadline)) {
        $deadlineDate = DateTime::createFromFormat('Y-m-d', $deadline);
        if (!$deadlineDate || $deadlineDate < new DateTime()) {
            $errors[] = 'Deadline must be a valid future date';
        }
    }

    if (empty($errors)) {
        try {
            if ($editing) {
                // Update existing job
                $stmt = $pdo->prepare("
                    UPDATE jobs SET
                        company_id = ?, department_id = ?, category_id = ?, title = ?, description = ?, responsibilities = ?, requirements = ?, 
                        nice_to_have = ?, perks = ?, location = ?, city = ?, state = ?, country = ?, work_mode = ?, 
                        employment_type = ?, salary = ?, salary_min = ?, salary_max = ?, salary_visible = ?, experience_required = ?, 
                        education = ?, openings = ?, deadline = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $company_id,
                    $department_id,
                    $category_id,
                    $title,
                    $description,
                    $responsibilities,
                    $requirements,
                    $nice_to_have,
                    $perks,
                    $location,
                    $city,
                    $state,
                    $country,
                    $work_mode,
                    $employment_type,
                    $salary,
                    $salary_min,
                    $salary_max,
                    $salary_visible,
                    $experience_required,
                    $education,
                    $openings,
                    $deadline ?: null,
                    $status,
                    $job_id
                ]);

                setFlash('success', 'Job updated successfully');
            } else {
                // Insert new job
                $stmt = $pdo->prepare("
                    INSERT INTO jobs (
                        company_id, department_id, category_id, title, description, responsibilities, requirements, 
                        nice_to_have, perks, location, city, state, country, work_mode, 
                        employment_type, salary, salary_min, salary_max, salary_visible, experience_required, 
                        education, openings, deadline, status, is_deleted, added_by, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    $company_id,
                    $department_id,
                    $category_id,
                    $title,
                    $description,
                    $responsibilities,
                    $requirements,
                    $nice_to_have,
                    $perks,
                    $location,
                    $city,
                    $state,
                    $country,
                    $work_mode,
                    $employment_type,
                    $salary,
                    $salary_min,
                    $salary_max,
                    $salary_visible,
                    $experience_required,
                    $education,
                    $openings,
                    $deadline ?: null,
                    $status,
                    0,
                    $_SESSION['user_id']
                ]);

                setFlash('success', 'Job added successfully');
            }

            // print all data 

            redirect('/apex-nexus-portal/admin/jobs.php');

        } catch (PDOException $e) {
            error_log("Job " . ($editing ? "update" : "add") . " error: " . $e->getMessage());
            setFlash('error', 'Failed to ' . ($editing ? 'update' : 'add') . ' job. Please try again.');
        }
    } else {
        setFlash('error', implode(', ', $errors));
    }
}

// Include admin sidebar
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
                <?php echo $editing ? 'Edit Job' : 'Add New Job'; ?>
            </h1>
            <p class="text-sm text-gray-500">
                <?php echo $editing ? 'Update job posting information' : 'Create a new job posting'; ?>
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
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <span class="text-sm font-medium text-gray-700 hidden sm:block">
                    <?php echo $_SESSION['user_name']; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Modern Tab-based Form Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-6xl mx-auto">
            <!-- Main Form Container -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">


                <form method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="add_job" value="1">

                    <!-- Tab 1: Basic Information -->
                    <div class="tab-content active" data-tab="1">
                        <div class="space-y-6">
                            <!-- Company Selection -->
                            <div>
                                <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Company <span class="text-red-500">*</span>
                                </label>
                                <select id="company_id" name="company_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (($editing && $job_data['company_id'] == $company['id']) || (isset($_POST['company_id']) && $_POST['company_id'] == $company['id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Department and Category Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Department
                                    </label>
                                    <select id="department_id" name="department_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?php echo $department['id']; ?>" <?php echo (($editing && $job_data['department_id'] == $department['id']) || (isset($_POST['department_id']) && $_POST['department_id'] == $department['id'])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($department['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Job Category
                                    </label>
                                    <select id="category_id" name="category_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo (($editing && $job_data['category_id'] == $category['id']) || (isset($_POST['category_id']) && $_POST['category_id'] == $category['id'])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Job Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Job Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="title" name="title"
                                    value="<?php echo htmlspecialchars($editing ? ($job_data['title'] ?? '') : ($_POST['title'] ?? '')); ?>"
                                    required placeholder="e.g., Senior Software Engineer"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Job Details -->
                    <div class="tab-content" data-tab="2">
                        <div class="space-y-6">
                            <!-- Job Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Job Description <span class="text-red-500">*</span>
                                </label>
                                <textarea id="description" name="description" rows="6" required
                                    placeholder="Provide a detailed description of the job role, what the candidate will be doing, and what makes this position exciting..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($editing ? ($job_data['description'] ?? '') : ($_POST['description'] ?? '')); ?></textarea>
                            </div>

                            <!-- Responsibilities and Requirements Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="responsibilities" class="block text-sm font-medium text-gray-700 mb-2">
                                        Key Responsibilities
                                    </label>
                                    <textarea id="responsibilities" name="responsibilities" rows="4"
                                        placeholder="List the main responsibilities and duties for this role..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($editing ? ($job_data['responsibilities'] ?? '') : ($_POST['responsibilities'] ?? '')); ?></textarea>
                                </div>

                                <div>
                                    <label for="requirements" class="block text-sm font-medium text-gray-700 mb-2">
                                        Required Qualifications
                                    </label>
                                    <textarea id="requirements" name="requirements" rows="4"
                                        placeholder="List the essential skills, experience, and qualifications required..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($editing ? ($job_data['requirements'] ?? '') : ($_POST['requirements'] ?? '')); ?></textarea>
                                </div>
                            </div>

                            <!-- Nice to Have and Perks Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nice_to_have" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nice to Have
                                    </label>
                                    <textarea id="nice_to_have" name="nice_to_have" rows="3"
                                        placeholder="Optional but preferred qualifications that would make a candidate stand out..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($editing ? ($job_data['nice_to_have'] ?? '') : ($_POST['nice_to_have'] ?? '')); ?></textarea>
                                </div>

                                <div>
                                    <label for="perks" class="block text-sm font-medium text-gray-700 mb-2">
                                        Perks & Benefits
                                    </label>
                                    <textarea id="perks" name="perks" rows="3"
                                        placeholder="List the benefits, perks, and company culture highlights..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($editing ? ($job_data['perks'] ?? '') : ($_POST['perks'] ?? '')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Location & Work Details -->
                    <div class="tab-content" data-tab="3">
                        <div class="space-y-6">
                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                    Location <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="location" name="location"
                                    value="<?php echo htmlspecialchars($editing ? ($job_data['location'] ?? '') : ($_POST['location'] ?? '')); ?>"
                                    required placeholder="e.g., New York, NY or Remote"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Address Details Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input type="text" id="city" name="city"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['city'] ?? '') : ($_POST['city'] ?? '')); ?>"
                                        placeholder="e.g., New York"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State/Province
                                    </label>
                                    <input type="text" id="state" name="state"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['state'] ?? '') : ($_POST['state'] ?? '')); ?>"
                                        placeholder="e.g., NY"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country
                                    </label>
                                    <input type="text" id="country" name="country"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['country'] ?? '') : ($_POST['country'] ?? '')); ?>"
                                        placeholder="e.g., USA"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Work Mode and Employment Type Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="work_mode" class="block text-sm font-medium text-gray-700 mb-2">
                                        Work Mode
                                    </label>
                                    <?php echo "<!-- work_mode from DB: [" . $job_data['work_mode'] . "] -->"; ?>
                                    <select id="work_mode" name="work_mode"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Work Mode</option>
                                        <?php foreach ($work_modes as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo (($editing && $job_data['work_mode'] === $value) || (!$editing && isset($_POST['work_mode']) && $_POST['work_mode'] === $value)) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        Employment Type
                                    </label>
                                    <select id="employment_type" name="employment_type"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select type</option>
                                        <?php foreach ($employment_types as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo (($editing && $job_data['employment_type'] === $value) || (!$editing && isset($_POST['employment_type']) && $_POST['employment_type'] === $value)) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 4: Salary & Requirements -->
                    <div class="tab-content" data-tab="4">
                        <div class="space-y-6">
                            <!-- Salary Range Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="salary" class="block text-sm font-medium text-gray-700 mb-2">
                                        Salary Range Display
                                    </label>
                                    <input type="text" id="salary" name="salary"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['salary'] ?? '') : ($_POST['salary'] ?? '')); ?>"
                                        placeholder="e.g., $80,000 - $120,000"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="salary_min" class="block text-sm font-medium text-gray-700 mb-2">
                                        Minimum Salary
                                    </label>
                                    <input type="number" id="salary_min" name="salary_min"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['salary_min'] ?? '') : ($_POST['salary_min'] ?? '')); ?>"
                                        placeholder="e.g., 80000"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="salary_max" class="block text-sm font-medium text-gray-700 mb-2">
                                        Maximum Salary
                                    </label>
                                    <input type="number" id="salary_max" name="salary_max"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['salary_max'] ?? '') : ($_POST['salary_max'] ?? '')); ?>"
                                        placeholder="e.g., 120000"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Salary Visibility and Experience Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Salary
                                        Visibility</label>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="salary_visible" name="salary_visible" value="1"
                                                <?php echo (isset($_POST['salary_visible']) && $_POST['salary_visible']) ? 'checked' : ''; ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="salary_visible" class="ml-2 text-sm text-gray-700">
                                                Show salary range to candidates
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label for="experience_required"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Experience Required
                                    </label>
                                    <input type="text" id="experience_required" name="experience_required"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['experience_required'] ?? '') : ($_POST['experience_required'] ?? '')); ?>"
                                        placeholder="e.g., 3+ years"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Education Required -->
                            <div>
                                <label for="education" class="block text-sm font-medium text-gray-700 mb-2">
                                    Education Required
                                </label>
                                <select id="education" name="education"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Education Level</option>
                                    <?php foreach ($education_levels as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (($editing && $job_data['education'] === $value) || (!$editing && isset($_POST['education']) && $_POST['education'] === $value)) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 5: Application Settings -->
                    <div class="tab-content" data-tab="5">
                        <div class="space-y-6">
                            <!-- Basic Settings Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="openings" class="block text-sm font-medium text-gray-700 mb-2">
                                        Number of Openings
                                    </label>
                                    <input type="number" id="openings" name="openings"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['openings'] ?? '1') : ($_POST['openings'] ?? '1')); ?>"
                                        min="1" placeholder="e.g., 1"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                        Application Deadline
                                    </label>
                                    <input type="date" id="deadline" name="deadline"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['deadline'] ?? '') : ($_POST['deadline'] ?? '')); ?>"
                                        min="<?php echo date('Y-m-d'); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Listing Settings Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="listing_plan" class="block text-sm font-medium text-gray-700 mb-2">
                                        Listing Plan
                                    </label>
                                    <select id="listing_plan" name="listing_plan"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select plan</option>
                                        <option value="Basic" <?php echo (isset($_POST['listing_plan']) && $_POST['listing_plan'] === 'Basic') ? 'selected' : ''; ?>>Basic</option>
                                        <option value="Featured" <?php echo (isset($_POST['listing_plan']) && $_POST['listing_plan'] === 'Featured') ? 'selected' : ''; ?>>Featured</option>
                                        <option value="Premium" <?php echo (isset($_POST['listing_plan']) && $_POST['listing_plan'] === 'Premium') ? 'selected' : ''; ?>>Premium</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="duration_days" class="block text-sm font-medium text-gray-700 mb-2">
                                        Listing Duration (Days)
                                    </label>
                                    <input type="number" id="duration_days" name="duration_days"
                                        value="<?php echo htmlspecialchars($editing ? ($job_data['duration_days'] ?? '30') : ($_POST['duration_days'] ?? '30')); ?>"
                                        min="1" placeholder="e.g., 30"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Application URL -->
                            <div>
                                <label for="apply_url" class="block text-sm font-medium text-gray-700 mb-2">
                                    Application URL
                                </label>
                                <input type="url" id="apply_url" name="apply_url"
                                    value="<?php echo htmlspecialchars($editing ? ($job_data['apply_url'] ?? '') : ($_POST['apply_url'] ?? '')); ?>"
                                    placeholder="https://company.com/careers/apply"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Category and Status Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        Job Status
                                    </label>
                                    <select id="status" name="status"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                        <option value="closed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                            <a href="/apex-nexus-portal/admin/jobs.php"
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition font-medium">
                                <?php echo $editing ? 'Update Job' : 'Create Job'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modern Tab Styles -->
    <style>
        .tab-button {
            @apply flex items-center px-1 py-4 border-b-2 text-sm font-medium transition-colors duration-200;
        }

        .tab-button:not(.active) {
            @apply border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300;
        }

        .tab-button.active {
            @apply border-blue-500 text-blue-600;
        }

        .tab-content {
            @apply hidden;
        }

        .tab-content.active {
            @apply block;
        }
    </style>

    <!-- Tab Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            function showTab(tabNumber) {
                // Hide all tabs
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Remove active class from all buttons
                tabButtons.forEach(button => {
                    button.classList.remove('active');
                });

                // Show selected tab
                document.querySelector(`.tab-content[data-tab="${tabNumber}"]`).classList.add('active');
                document.querySelector(`.tab-button[data-tab="${tabNumber}"]`).classList.add('active');
            }

            // Tab button clicks
            tabButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const tabNumber = parseInt(this.getAttribute('data-tab'));
                    showTab(tabNumber);
                });
            });
        });
    </script>

    <!-- Tab Navigation Styles -->
    <style>
        .tab-button {
            @apply flex-1 flex flex-col items-center justify-center py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200;
        }

        .tab-button:not(.active) {
            @apply border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300;
        }

        .tab-button.active {
            @apply border-blue-500 text-blue-600;
        }

        .tab-number {
            @apply w-8 h-8 rounded-full border-2 border-current flex items-center justify-center mb-2 text-sm font-semibold;
        }

        .tab-button.active .tab-number {
            @apply bg-blue-500 text-white border-blue-500;
        }

        .tab-button:not(.active) .tab-number {
            @apply border-gray-300 text-gray-500;
        }

        .tab-content {
            @apply hidden;
        }

        .tab-content.active {
            @apply block;
        }
    </style>

    <!-- Tab Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            const nextButtons = document.querySelectorAll('.next-tab-btn');
            const prevButtons = document.querySelectorAll('.prev-tab-btn');

            function showTab(tabNumber) {
                // Hide all tabs
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Remove active class from all buttons
                tabButtons.forEach(button => {
                    button.classList.remove('active');
                });

                // Show selected tab
                document.querySelector(`.tab-content[data-tab="${tabNumber}"]`).classList.add('active');
                document.querySelector(`.tab-button[data-tab="${tabNumber}"]`).classList.add('active');
            }

            // Tab button clicks
            tabButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const tabNumber = this.getAttribute('data-tab');
                    showTab(tabNumber);
                });
            });

            // Next button clicks
            nextButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const currentTab = this.closest('.tab-content');
                    const currentTabNumber = parseInt(currentTab.getAttribute('data-tab'));
                    const nextTabNumber = currentTabNumber + 1;

                    if (nextTabNumber <= 5) {
                        showTab(nextTabNumber);
                    }
                });
            });

            // Previous button clicks
            prevButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const currentTab = this.closest('.tab-content');
                    const currentTabNumber = parseInt(currentTab.getAttribute('data-tab'));
                    const prevTabNumber = currentTabNumber - 1;

                    if (prevTabNumber >= 1) {
                        showTab(prevTabNumber);
                    }
                });
            });
        });
    </script>
</div>

<?php require_once '../includes/footer.php'; ?>