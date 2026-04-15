<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Post a Job - Apex Nexus";
require_once '../includes/header.php';
?>

<!-- Company CSS Imports -->
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-modern.css">

<?php
$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get company record
$stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company['id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $errors = [];
    $title = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $employment_type = clean($_POST['employment_type'] ?? '');
    $work_mode = clean($_POST['work_mode'] ?? '');
    $city = clean($_POST['city'] ?? '');
    
    if (empty($title)) $errors[] = 'Job title is required';
    if (empty($description)) $errors[] = 'Job description is required';
    if (empty($employment_type)) $errors[] = 'Employment type is required';
    if (empty($work_mode)) $errors[] = 'Work mode is required';
    if (empty($city)) $errors[] = 'City is required';
    
    if (empty($errors)) {
        try {
            // Insert job
            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    company_id, department_id, category_id, title, description, 
                    responsibilities, requirements, nice_to_have, perks, 
                    location, city, state, country, work_mode, employment_type, 
                    salary, salary_min, salary_max, salary_visible, 
                    experience_required, education, openings, deadline, 
                    status, is_deleted, added_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $companyId,
                (int) ($_POST['department_id'] ?? 0),
                (int) ($_POST['category_id'] ?? 0),
                $title,
                $description,
                clean($_POST['responsibilities'] ?? ''),
                clean($_POST['requirements'] ?? ''),
                clean($_POST['nice_to_have'] ?? ''),
                clean($_POST['perks'] ?? ''),
                clean($_POST['location'] ?? ''),
                $city,
                clean($_POST['state'] ?? ''),
                clean($_POST['country'] ?? 'India'),
                $work_mode,
                $employment_type,
                clean($_POST['salary'] ?? ''),
                clean($_POST['salary_min'] ?? ''),
                clean($_POST['salary_max'] ?? ''),
                isset($_POST['salary_visible']) ? 1 : 0,
                clean($_POST['experience_required'] ?? ''),
                clean($_POST['education'] ?? ''),
                (int) ($_POST['openings'] ?? 1),
                clean($_POST['deadline'] ?? ''),
                'active', // or 'pending' if you want admin approval
                0,
                $userId
            ]);
            
            setFlash('success', 'Job posted successfully!');
            redirect('/apex-nexus-portal/company/manage-jobs.php');
            
        } catch (PDOException $e) {
            error_log("Job posting error: " . $e->getMessage());
            setFlash('error', 'Failed to post job. Please try again.');
        }
    } else {
        setFlash('error', implode(', ', $errors));
    }
}

// Fetch departments and categories for dropdowns
try {
    $stmt = $pdo->prepare("SELECT * FROM job_departments WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $departments = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM job_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $departments = $categories = [];
}
?>

<!-- Modern Company Navigation -->
<?php include '../includes/company-navbar.php'; ?>

<div class="min-h-screen bg-gray-50 p-6 lg:p-8">
    <div class="max-w-7xl mx-auto mt-20">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column - Info Card -->
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-blue-50 via-white to-purple-50 rounded-2xl p-6 border border-gray-100 shadow-xl backdrop-blur-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/20 to-purple-400/20 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-br from-purple-400/20 to-pink-400/20 rounded-full blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-4">
                            Post a New Job
                        </h2>
                        <p class="text-gray-600 mb-6">
                            Create an attractive job posting to find the perfect candidates for your team.
                        </p>
                        
                        <div class="space-y-4">
                            <div class="bg-white/50 backdrop-blur-sm rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Job Basics</p>
                                        <p class="text-xs text-gray-500">Title, type, and mode</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white/50 backdrop-blur-sm rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Job Details</p>
                                        <p class="text-xs text-gray-500">Description & requirements</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white/50 backdrop-blur-sm rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Location & Salary</p>
                                        <p class="text-xs text-gray-500">Location and compensation</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Form -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-pink-400/10 rounded-full blur-2xl"></div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-200/50 backdrop-blur-sm relative z-10">
                        <nav class="flex -mb-px" aria-label="Tabs">
                            <button onclick="showTab('basics')" id="tab-basics" class="tab-button active py-4 px-6 border-b-2 border-blue-500 font-medium text-sm text-blue-600 bg-gradient-to-r from-blue-50 to-transparent transition-all duration-300 hover:from-blue-100">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                Job Basics
                            </button>
                            <button onclick="showTab('details')" id="tab-details" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent transition-all duration-300">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 8v2M4 4v2M4 4h2m8 0h2M4 4v2m0 0h8m0 0v2m0-2h2m-4 4h-4m-4 0H4m0 0V8m0 0v2m0 0v2m0-2h2m-4 4h-4" clip-rule="evenodd"/>
                                </svg>
                                Job Details
                            </button>
                            <button onclick="showTab('location')" id="tab-location" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gradient-to-r hover:from-purple-50 hover:to-transparent transition-all duration-300">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                Location & Salary
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6 relative z-10">
                        <form method="POST">
                            
                            <!-- Job Basics Tab -->
                            <div id="content-basics" class="tab-content">
                                <h3 class="text-lg font-semibold text-gray-800 mb-6">Job Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Job Title *</label>
                                        <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. Senior PHP Developer" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                            <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                                <option value="">Select Department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept['id']; ?>" <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                            <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                                            <select name="employment_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                                <option value="">Select Type</option>
                                                <option value="full-time" <?php echo (($_POST['employment_type'] ?? '') == 'full-time') ? 'selected' : ''; ?>>Full-time</option>
                                                <option value="part-time" <?php echo (($_POST['employment_type'] ?? '') == 'part-time') ? 'selected' : ''; ?>>Part-time</option>
                                                <option value="contract" <?php echo (($_POST['employment_type'] ?? '') == 'contract') ? 'selected' : ''; ?>>Contract</option>
                                                <option value="internship" <?php echo (($_POST['employment_type'] ?? '') == 'internship') ? 'selected' : ''; ?>>Internship</option>
                                                <option value="freelance" <?php echo (($_POST['employment_type'] ?? '') == 'freelance') ? 'selected' : ''; ?>>Freelance</option>
                                                <option value="temporary" <?php echo (($_POST['employment_type'] ?? '') == 'temporary') ? 'selected' : ''; ?>>Temporary</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Work Mode *</label>
                                            <select name="work_mode" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                                <option value="">Select Mode</option>
                                                <option value="on-site" <?php echo (($_POST['work_mode'] ?? '') == 'on-site') ? 'selected' : ''; ?>>On-site</option>
                                                <option value="remote" <?php echo (($_POST['work_mode'] ?? '') == 'remote') ? 'selected' : ''; ?>>Remote</option>
                                                <option value="hybrid" <?php echo (($_POST['work_mode'] ?? '') == 'hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Number of Openings</label>
                                            <input type="number" name="openings" min="1" value="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Application Deadline</label>
                                            <input type="date" name="deadline" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-end">
                                    <button type="button" onclick="showTab('details')" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
                                        Next: Job Details →
                                    </button>
                                </div>
                            </div>

                            <!-- Job Details Tab -->
                            <div id="content-details" class="tab-content hidden">
                                <h3 class="text-lg font-semibold text-gray-800 mb-6">Job Description</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Job Description *</label>
                                        <textarea name="description" rows="5" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Describe the role and responsibilities..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Key Responsibilities</label>
                                        <textarea name="responsibilities" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="List the key responsibilities for this role..."><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Requirements *</label>
                                        <textarea name="requirements" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Must-have qualifications and skills..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nice to Have</label>
                                        <textarea name="nice_to_have" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Bonus skills that would be great..."><?php echo htmlspecialchars($_POST['nice_to_have'] ?? ''); ?></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Perks & Benefits</label>
                                        <textarea name="perks" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="What you offer to candidates..."><?php echo htmlspecialchars($_POST['perks'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-between">
                                    <button type="button" onclick="showTab('basics')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                        ← Back
                                    </button>
                                    <button type="button" onclick="showTab('location')" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
                                        Next: Location & Salary →
                                    </button>
                                </div>
                            </div>

                            <!-- Location & Salary Tab -->
                            <div id="content-location" class="tab-content hidden">
                                <h3 class="text-lg font-semibold text-gray-800 mb-6">Location & Compensation</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                            <input type="text" name="city" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                            <input type="text" name="state" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                            <input type="text" name="country" value="India" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Location/Area</label>
                                        <input type="text" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. SG Highway, Ahmedabad" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Salary Min (Optional)</label>
                                            <input type="text" name="salary_min" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. 500000" value="<?php echo htmlspecialchars($_POST['salary_min'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Salary Max (Optional)</label>
                                            <input type="text" name="salary_max" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. 800000" value="<?php echo htmlspecialchars($_POST['salary_max'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Salary Text</label>
                                            <input type="text" name="salary" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. 5-8 LPA" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" name="salary_visible" id="salary_visible" <?php echo isset($_POST['salary_visible']) ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <label for="salary_visible" class="ml-2 text-sm text-gray-700">Show salary to candidates</label>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Experience Required</label>
                                            <input type="text" name="experience_required" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. 3-5 years" value="<?php echo htmlspecialchars($_POST['experience_required'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Education Required</label>
                                            <input type="text" name="education" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g. B.Tech/BE" value="<?php echo htmlspecialchars($_POST['education'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Apply Email</label>
                                        <input type="email" name="apply_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="applications@company.com" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">External Apply URL (Optional)</label>
                                        <input type="url" name="apply_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="https://careers.company.com" value="<?php echo htmlspecialchars($_POST['apply_url'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-between">
                                    <button type="button" onclick="showTab('details')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                        ← Back
                                    </button>
                                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-300 font-medium shadow-lg">
                                        Post Job
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600', 'bg-gradient-to-r', 'from-blue-50', 'to-transparent');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.add('active', 'border-blue-500', 'text-blue-600', 'bg-gradient-to-r', 'from-blue-50', 'to-transparent');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
}
</script>

<?php require_once '../includes/footer.php'; ?>