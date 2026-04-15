<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Company Profile - Apex Nexus";
require_once '../includes/header.php';
?>

<!-- Company CSS Imports -->
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-modern.css">

<?php
$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $companyName = clean($_POST['company_name'] ?? '');
    $city = clean($_POST['city'] ?? '');
    $state = clean($_POST['state'] ?? '');
    $country = clean($_POST['country'] ?? '');
    
    if (empty($companyName)) $errors[] = 'Company name is required';
    if (empty($city)) $errors[] = 'City is required';
    
    // Handle logo upload
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        error_log("Logo upload attempt detected. File info: " . print_r($_FILES['logo'], true));
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.';
            error_log("Invalid file type: " . $_FILES['logo']['type']);
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = 'File size must be less than 5MB.';
            error_log("File too large: " . $_FILES['logo']['size']);
        } else {
            $uploadDir = __DIR__ . '/../assets/uploads/company-logos/';
            error_log("Upload directory: " . $uploadDir);
            
            if (!is_dir($uploadDir)) {
                error_log("Directory does not exist, creating: " . $uploadDir);
                if (!mkdir($uploadDir, 0777, true)) {
                    $errors[] = 'Failed to create upload directory.';
                    error_log("Failed to create directory: " . $uploadDir);
                }
            }
            
            if (is_dir($uploadDir) && is_writable($uploadDir)) {
                $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $fileName = 'company_' . $userId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                error_log("Attempting to move file to: " . $uploadPath);
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                    $logoPath = 'assets/uploads/company-logos/' . $fileName;
                    error_log("Logo uploaded successfully: " . $logoPath);
                } else {
                    $errors[] = 'Failed to upload logo. Please try again.';
                    error_log("move_uploaded_file failed. Temp file: " . $_FILES['logo']['tmp_name']);
                }
            } else {
                $errors[] = 'Upload directory is not writable.';
                error_log("Directory not writable: " . $uploadDir);
            }
        }
    } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        error_log("Logo upload error code: " . $_FILES['logo']['error']);
        $errors[] = 'Logo upload error: ' . $_FILES['logo']['error'];
    }
    
    if (empty($errors)) {
        try {
            // Update company profile
            $stmt = $pdo->prepare("
                UPDATE companies SET 
                    company_name = ?, website = ?, description = ?, 
                    city = ?, state = ?, country = ?, 
                    address_line1 = ?, address_line2 = ?, phone = ?, 
                    updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            
            $stmt->execute([
                $companyName,
                clean($_POST['website'] ?? ''),
                clean($_POST['description'] ?? ''),
                $city,
                $state,
                $country,
                clean($_POST['address_line1'] ?? ''),
                clean($_POST['address_line2'] ?? ''),
                clean($_POST['phone'] ?? ''),
                $userId
            ]);
            
            // Update logo if uploaded
            if ($logoPath) {
                $stmt = $pdo->prepare("UPDATE companies SET logo = ? WHERE user_id = ? AND is_deleted = 0");
                $stmt->execute([$logoPath, $userId]);
            }
            
            setFlash('success', 'Company profile updated successfully!');
            redirect('/apex-nexus-portal/company/profile.php');
            
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            setFlash('error', 'Failed to update profile. Please try again.');
        }
    } else {
        setFlash('error', implode(', ', $errors));
    }
}

// Get company data
try {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$userId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        setFlash('error', 'Company profile not found');
        redirect('/apex-nexus-portal/company/dashboard.php');
    }
    
} catch (PDOException $e) {
    error_log("Company fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load company data');
    redirect('/apex-nexus-portal/company/dashboard.php');
}
?>

<!-- Modern Company Navigation -->
<?php include '../includes/company-navbar.php'; ?>

<div class="min-h-screen bg-gray-50  pt-28">
    <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-2">
        Company Profile
      </h1>
      <p class="text-gray-600">Manage your company information and showcase your brand.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 mt-4">
      <!-- Left Column - Form -->
      <div class="lg:col-span-2">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- Basic Information -->
          <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <h2 class="text-lg font-bold text-gray-900 mb-6 relative z-10">Basic Information</h2>
            
            <div class="space-y-6 relative z-10">
              <!-- Logo Upload -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
                <div class="flex items-center gap-6">
                  <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full blur opacity-75 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full overflow-hidden shadow-xl">
                      <?php 
                      if ($company['logo']): ?>
                        <img src="/apex-nexus-portal/<?php echo htmlspecialchars($company['logo']); ?>" 
                             alt="Company Logo" class="w-full h-full object-cover">
                      <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100">
                          <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            <?php echo strtoupper(substr($company['company_name'], 0, 1)); ?>
                          </span>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div>
                    <input type="file" name="logo" accept="image/*" class="hidden" id="logoInput">
                    <button type="button" onclick="document.getElementById('logoInput').click()" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
                      Change Logo
                    </button>
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, GIF, WebP up to 5MB</p>
                  </div>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                <input type="text" name="company_name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                       value="<?php echo htmlspecialchars($company['company_name']); ?>">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                <input type="url" name="website"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                       value="<?php echo htmlspecialchars($company['website']); ?>">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="4"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                          placeholder="Tell candidates about your company..."><?php echo htmlspecialchars($company['description']); ?></textarea>
              </div>
            </div>
          </div>

          <!-- Location Information -->
          <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <h2 class="text-lg font-bold text-gray-900 mb-6 relative z-10">Location Information</h2>
            
            <div class="space-y-6 relative z-10">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                  <input type="text" name="city" required
                         class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                         value="<?php echo htmlspecialchars($company['city']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                  <input type="text" name="state"
                         class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                         value="<?php echo htmlspecialchars($company['state']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                  <input type="text" name="country"
                         class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                         value="<?php echo htmlspecialchars($company['country']); ?>">
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1</label>
                <input type="text" name="address_line1"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                       value="<?php echo htmlspecialchars($company['address_line1'] ?? ''); ?>">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                <input type="text" name="address_line2"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                       value="<?php echo htmlspecialchars($company['address_line2'] ?? ''); ?>">
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <h2 class="text-lg font-bold text-gray-900 mb-6 relative z-10">Contact Information</h2>
            
            <div class="space-y-6 relative z-10">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="tel" name="phone"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                       value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <a href="/apex-nexus-portal/company/dashboard.php" 
               class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
              Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
              Save Changes
            </button>
          </div>
        </form>
      </div>

      <!-- Right Column - Preview -->
      <div class="lg:col-span-1">
        <div class="sticky top-24 space-y-6">
          <!-- Profile Preview -->
          <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <h3 class="text-lg font-bold text-gray-900 mb-4 relative z-10">Profile Preview</h3>
            
            <div class="text-center mb-6 relative z-10">
              <div class="relative group inline-block mb-4">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full blur opacity-75 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full overflow-hidden shadow-xl">
                  <?php 
                  if ($company['logo']): ?>
                    <img src="/apex-nexus-portal/<?php echo htmlspecialchars($company['logo']); ?>" 
                         alt="Company Logo" class="w-full h-full object-cover">
                  <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100">
                      <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        <?php echo strtoupper(substr($company['company_name'], 0, 1)); ?>
                      </span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <h4 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($company['company_name']); ?></h4>
              <?php if ($company['website']): ?>
                <p class="text-gray-600 text-sm mt-1">
                  <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-blue-600 hover:text-blue-700">
                    <?php echo htmlspecialchars($company['website']); ?>
                  </a>
                </p>
              <?php endif; ?>
            </div>
            
            <div class="space-y-3 text-sm relative z-10">
              <?php if ($company['city']): ?>
                <div class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  <?php echo htmlspecialchars($company['city']); ?>
                  <?php if ($company['state']): ?>
                    , <?php echo htmlspecialchars($company['state']); ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              
              <?php if ($company['phone']): ?>
                <div class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                  </svg>
                  <?php echo htmlspecialchars($company['phone']); ?>
                </div>
              <?php endif; ?>
            </div>
            
            <?php if ($company['description']): ?>
              <div class="mt-6 pt-6 border-t border-gray-200 relative z-10">
                <h5 class="font-medium text-gray-900 mb-2">About</h5>
                <p class="text-sm text-gray-600 line-clamp-3">
                  <?php echo htmlspecialchars($company['description']); ?>
                </p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Completion Status -->
          <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <h3 class="text-lg font-bold text-gray-900 mb-4 relative z-10">Profile Completion</h3>
            <div class="space-y-3 relative z-10">
              <?php
              $fields = [
                'company_name' => 'Company Name',
                'city' => 'Location',
                'description' => 'Description',
                'website' => 'Website',
                'logo' => 'Logo'
              ];
              
              $completed = 0;
              $total = count($fields);
              
              foreach ($fields as $field => $label) {
                $isComplete = !empty($company[$field]);
                if ($isComplete) $completed++;
              ?>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700"><?php echo $label; ?></span>
                  <?php if ($isComplete): ?>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  <?php else: ?>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  <?php endif; ?>
                </div>
              <?php } ?>
            </div>
            
            <div class="mt-6 relative z-10">
              <div class="flex justify-between text-sm text-gray-700 mb-2">
                <span>Completion</span>
                <span><?php echo round(($completed / $total) * 100); ?>%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-2 rounded-full" style="width: <?php echo ($completed / $total) * 100; ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<?php require_once '../includes/footer.php'; ?>