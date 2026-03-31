<?php
/**
 * Admin Add Job Page
 */

$pageTitle = "Add Job - Admin";

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Fetch companies for dropdown
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $companiesStmt = $pdo->query("
    SELECT c.id, c.company_name as company_name, u.name as user_name 
    FROM companies c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.is_deleted = 0
    ORDER BY c.company_name ASC
");
    $companies = $companiesStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Companies fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load companies data');
    $companies = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    // Sanitize inputs
    $title = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $salary = clean($_POST['salary'] ?? '');
    $category = clean($_POST['category'] ?? '');
    $deadline = clean($_POST['deadline'] ?? '');
    $company_id = (int)($_POST['company_id'] ?? 0);
    
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
    if (empty($category)) {
        $errors[] = 'Category is required';
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
            $stmt = $pdo->prepare("
                INSERT INTO jobs (company_id, title, description, location, salary, category, status, deadline, added_by, is_deleted, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, 0, NOW(), NOW())
            ");
            $stmt->execute([
                $company_id,
                $title,
                $description,
                $location,
                $salary ?: 'N/A',
                $category,
                $deadline ?: null,
                $_SESSION['user_id']
            ]);
            
            setFlash('success', 'Job added successfully');
            redirect('/apex-nexus-portal/admin/jobs.php');
            
        } catch (PDOException $e) {
            error_log("Job add error: " . $e->getMessage());
            setFlash('error', 'Failed to add job. Please try again.');
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
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800">Add New Job</h1>
            <p class="text-sm text-gray-500">Create a new job posting</p>
        </div>
        
        <!-- Right Side -->
        <div class="flex items-center gap-4">
            <!-- Notification -->
            <button class="relative p-2 rounded-full hover:bg-gray-100 transition">
                🔔
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- Profile -->
            <div class="flex items-center gap-3 bg-gray-100 px-3 py-2 rounded-full cursor-pointer hover:bg-gray-200 transition">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <span class="text-sm font-medium text-gray-700 hidden sm:block">
                    <?php echo $_SESSION['user_name']; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="add_job" value="1">
                    
                    <!-- Company Selection -->
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Company <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="company_id" 
                            name="company_id" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select a company</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>" <?php echo (isset($_POST['company_id']) && $_POST['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($company['company_name'] . ' (' . $company['user_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Job Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Job Title <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                            required
                            placeholder="e.g., Senior Software Engineer"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <!-- Job Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Job Description <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="6"
                            required
                            placeholder="Provide detailed job description, responsibilities, and requirements..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Location and Salary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                Location <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="location" 
                                name="location" 
                                value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                required
                                placeholder="e.g., New York, NY or Remote"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        
                        <div>
                            <label for="salary" class="block text-sm font-medium text-gray-700 mb-2">
                                Salary (Optional)
                            </label>
                            <input 
                                type="text" 
                                id="salary" 
                                name="salary" 
                                value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>"
                                placeholder="e.g., $80,000 - $120,000"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                    
                    <!-- Category and Deadline -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="category" 
                                name="category" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select category</option>
                                <option value="Technology" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                <option value="Marketing" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Sales" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                <option value="Finance" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="HR" <?php echo (isset($_POST['category']) && $_POST['category'] === 'HR') ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Operations" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                <option value="Customer Service" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Customer Service') ? 'selected' : ''; ?>>Customer Service</option>
                                <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                Application Deadline (Optional)
                            </label>
                            <input 
                                type="date" 
                                id="deadline" 
                                name="deadline" 
                                value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>"
                                min="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <a href="/apex-nexus-portal/admin/jobs.php" 
                           class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button 
                            type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out"
                        >
                            Add Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
