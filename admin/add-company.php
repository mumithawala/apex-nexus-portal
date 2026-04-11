<?php
/**
 * Admin Add Company Page
 */

// Include header
require_once '../includes/header.php';

// Protect page - admin only
requireRole('admin');

// Check if we're in edit mode
$isEdit = isset($_GET['id']);
$companyId = $isEdit ? (int)$_GET['id'] : 0;
$company = null;

$pageTitle = $isEdit ? "Edit Company - Admin" : "Add Company - Admin";

// If editing, fetch existing company data
if ($isEdit && $companyId > 0) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT c.*, u.first_name, u.last_name, u.email 
            FROM companies c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.id = ? AND c.is_deleted = 0
        ");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch();
        
        if (!$company) {
            setFlash('error', 'Company not found');
            redirect('admin/companies.php');
        }
    } catch (PDOException $e) {
        error_log("Company fetch error: " . $e->getMessage());
        setFlash('error', 'Failed to load company data');
        redirect('admin/companies.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $companyName = clean($_POST['company_name']);
    $contactPersonFirstName = clean($_POST['contact_person_first_name']);
    $contactPersonLastName = clean($_POST['contact_person_last_name']);
    $email = clean($_POST['email']);
    $phone = clean($_POST['phone']);
    $website = clean($_POST['website']);
    $description = clean($_POST['description']);
   
    
    // Validate inputs
    $errors = [];
    if (empty($companyName)) {
        $errors[] = 'Company name is required';
    }
    if (empty($contactPersonFirstName)) {
        $errors[] = 'Contact person first name is required';
    }
    if (empty($contactPersonLastName)) {
        $errors[] = 'Contact person last name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            if ($isEdit) {
                // Update existing user
                $userStmt = $pdo->prepare("
                    UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $userStmt->execute([$contactPersonFirstName, $contactPersonLastName, $email, $company['user_id']]);
                
                // Update existing company with all fields
                $companyStmt = $pdo->prepare("
                    UPDATE companies SET 
                        company_name = ?, 
                        description = ?, 
                        website = ?, 
                        city = ?, 
                        state = ?, 
                        country = ?, 
                        address_line1 = ?, 
                        address_line2 = ?, 
                        phone = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $companyStmt->execute([
                    $companyName, 
                    $description, 
                    $website, 
                    $_POST['city'] ?? '', 
                    $_POST['state'] ?? '', 
                    $_POST['country'] ?? '', 
                    $_POST['address_line1'] ?? '', 
                    $_POST['address_line2'] ?? '', 
                    $phone, 
                    $companyId
                ]);
                
                $successMessage = 'Company updated successfully!';
            } else {
                // Create new user account first
                $hashedPassword = password_hash('default123', PASSWORD_DEFAULT);
                $userStmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, email, password, role, is_deleted, added_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 'company', 0, ?, NOW(), NOW())
                ");
                $userStmt->execute([$contactPersonFirstName, $contactPersonLastName, $email, $hashedPassword, $_SESSION['user_id']]);
                $userId = $pdo->lastInsertId();
                
                // Create company record - only use existing columns
                $companyStmt = $pdo->prepare("
                    INSERT INTO companies (
                        user_id, company_name, logo, description, website, 
                        city, state, country, address_line1, address_line2, 
                        phone, is_deleted, added_by, created_at, updated_at
                    ) VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())
                ");
                $companyStmt->execute([
                    $userId,
                    $companyName,
                    $description,
                    $website,
                    $_POST['city'] ?? '',
                    $_POST['state'] ?? '',
                    $_POST['country'] ?? '',
                    $_POST['address_line1'] ?? '',
                    $_POST['address_line2'] ?? '',
                    $phone,
                    $_SESSION['user_id']
                ]);
                
                $successMessage = 'Company added successfully! Default password: default123';
            }
            
            $pdo->commit();
            setFlash('success', $successMessage);
            redirect('admin/companies.php');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Company " . ($isEdit ? "update" : "creation") . " error: " . $e->getMessage());
            setFlash('error', 'Failed to ' . ($isEdit ? 'update' : 'add') . ' company. Email might already exist.');
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
    <div class="bg-white/80 backdrop-blur-md shadow-sm border-b px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Left Side -->
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800"><?php echo $isEdit ? 'Edit Company' : 'Add New Company'; ?></h1>
            <p class="text-sm text-gray-500"><?php echo $isEdit ? 'Update company information' : 'Register a new company in system'; ?></p>
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
        <div class="max-w-3xl mx-auto">
            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="add_company" value="1">
                        
                        <!-- Company Information Section -->
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex justify-center items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Company Information
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Company Name -->
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Company Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="company_name" 
                                        name="company_name" 
                                        required
                                        value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter company name"
                                    >
                                </div>
                                
                                <!-- Contact Person First Name -->
                                <div>
                                    <label for="contact_person_first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Contact Person First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="contact_person_first_name" 
                                        name="contact_person_first_name" 
                                        required
                                        value="<?php echo htmlspecialchars($company['first_name'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter contact person first name"
                                    >
                                </div>
                                
                                <!-- Contact Person Last Name -->
                                <div>
                                    <label for="contact_person_last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Contact Person Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="contact_person_last_name" 
                                        name="contact_person_last_name" 
                                        required
                                        value="<?php echo htmlspecialchars($company['last_name'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter contact person last name"
                                    >
                                </div>
                                
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        required
                                        value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="company@example.com"
                                    >
                                </div>
                                
                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="+1 234 567 8900"
                                    >
                                </div>
                                
                                <!-- Website -->
                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                        Website
                                    </label>
                                    <input 
                                        type="url" 
                                        id="website" 
                                        name="website" 
                                        value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://www.example.com"
                                    >
                                </div>
                                
                               
                                
                                <!-- City -->
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input 
                                        type="text" 
                                        id="city" 
                                        name="city" 
                                        value="<?php echo htmlspecialchars($company['city'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter city"
                                    >
                                </div>
                                
                                <!-- State -->
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State
                                    </label>
                                    <input 
                                        type="text" 
                                        id="state" 
                                        name="state" 
                                        value="<?php echo htmlspecialchars($company['state'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter state"
                                    >
                                </div>
                                
                                <!-- Country -->
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country
                                    </label>
                                    <input 
                                        type="text" 
                                        id="country" 
                                        name="country" 
                                        value="<?php echo htmlspecialchars($company['country'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter country"
                                    >
                                </div>
                                
                                <!-- Address Line 1 -->
                                <div>
                                    <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-2">
                                        Address Line 1
                                    </label>
                                    <input 
                                        type="text" 
                                        id="address_line1" 
                                        name="address_line1" 
                                        value="<?php echo htmlspecialchars($company['address_line1'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Street address"
                                    >
                                </div>
                                
                                <!-- Address Line 2 -->
                                <div>
                                    <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-2">
                                        Address Line 2
                                    </label>
                                    <input 
                                        type="text" 
                                        id="address_line2" 
                                        name="address_line2" 
                                        value="<?php echo htmlspecialchars($company['address_line2'] ?? ''); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Apartment, suite, etc."
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description Section -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Company Description
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Brief description of company..."
                            ><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-center gap-4 pt-4">
                            <button 
                                type="submit" 
                                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg shadow hover:scale-105 transition font-semibold text-base flex items-center"
                            >
                                
                                <?php echo $isEdit ? 'Update Company' : 'Add Company'; ?>
                            </button>
                            
                            <a 
                                href="/apex-nexus-portal/admin/companies.php"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold text-base"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800"><?php echo $isEdit ? 'Update Information' : 'Important Information'; ?></h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <?php if ($isEdit): ?>
                                <p>• Updating company information will modify the existing record</p>
                                <p>• User account details will also be updated</p>
                                <p>• Changes will be reflected immediately</p>
                            <?php else: ?>
                                <p>• A user account will be automatically created for company</p>
                                <p>• Default password: <code class="bg-blue-100 px-1 rounded">default123</code></p>
                                <p>• The company can change their password after first login</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
