<?php
/**
 * Admin Add Category Page
 */

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Check if we're in edit mode
$isEdit = isset($_GET['id']);
$categoryId = $isEdit ? (int)$_GET['id'] : 0;
$category = null;

$pageTitle = $isEdit ? "Edit Category - Admin" : "Add Category - Admin";

// If editing, fetch existing category data
if ($isEdit && $categoryId > 0) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM job_categories 
            WHERE id = ?
        ");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        
        if (!$category) {
            setFlash('error', 'Category not found');
            redirect('/apex-nexus-portal/admin/add-category.php');
        }
    } catch (PDOException $e) {
        error_log("Category fetch error: " . $e->getMessage());
        setFlash('error', 'Failed to load category data');
        redirect('/apex-nexus-portal/admin/add-category.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = clean($_POST['name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Category name is required';
    }
    
  
    
    
    // If no errors, insert/update category
    if (empty($errors)) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            if ($isEdit) {
                // Update existing category
                $stmt = $pdo->prepare("
                    UPDATE job_categories SET 
                        name = ?, 
                        is_active = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $is_active, $categoryId]);
                $successMessage = 'Category updated successfully!';
            } else {
                // Insert new category
                $stmt = $pdo->prepare("
                    INSERT INTO job_categories (name, is_active, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$name, $is_active, $_SESSION['user_id']]);
                $successMessage = 'Category added successfully!';
            }
            
            setFlash('success', $successMessage);
            redirect('/apex-nexus-portal/admin/add-category.php');
            
        } catch (PDOException $e) {
            error_log("Category " . ($isEdit ? "update" : "insert") . " error: " . $e->getMessage());
            $errors[] = 'Failed to ' . ($isEdit ? 'update' : 'add') . ' category. Please try again.';
        }
    }
}

require_once '../includes/admin-sidebar.php';
?>

<!-- Main Content -->
<div class="lg:pl-64 bg-gray-50 min-h-screen">
    <!-- Top Header -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800"><?php echo $isEdit ? 'Edit Category' : 'Add New Category'; ?></h1>
            <p class="text-sm text-gray-500"><?php echo $isEdit ? 'Update category information' : 'Create a new job category in system'; ?></p>
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
        <div class="max-w-xl mx-auto">
            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="add_category" value="1">
                        
                        <!-- Category Information Section -->
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex justify-center items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Category Information
                            </h2>
                            
                            <div class="grid grid-cols-1 gap-6">
                                <!-- Category Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Category Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        required
                                        value="<?php echo htmlspecialchars($category['name'] ?? ($_POST['name'] ?? '')); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., Technology, Marketing, Sales"
                                    >
                                </div>
                                
                            </div>
                            
                          
                        </div>
                        
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Status -->
                        <div class="mb-6">
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="is_active" 
                                    name="is_active" 
                                    value="1"
                                    <?php echo (isset($_POST['is_active']) && $_POST['is_active']) || (!$isEdit) ? 'checked' : ''; ?>
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label for="is_active" class="ml-2 text-sm text-gray-700">
                                    Active
                                </label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Uncheck to create this category as inactive.
                            </p>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-center gap-4 pt-6 border-t border-gray-200">
                            <a href="/apex-nexus-portal/admin/add-category.php" 
                               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition text-sm font-medium"
                            >
                                <?php echo $isEdit ? 'Update Category' : 'Add Category'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
