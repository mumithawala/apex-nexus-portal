<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Company Profile - Apex Nexus";
require_once '../includes/header.php';

$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $companyName = clean($_POST['company_name'] ?? '');
    $industry = clean($_POST['industry'] ?? '');
    $city = clean($_POST['city'] ?? '');
    $state = clean($_POST['state'] ?? '');
    $country = clean($_POST['country'] ?? '');
    
    if (empty($companyName)) $errors[] = 'Company name is required';
    if (empty($industry)) $errors[] = 'Industry is required';
    if (empty($city)) $errors[] = 'City is required';
    
    // Handle logo upload
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.';
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = 'File size must be less than 5MB.';
        } else {
            $uploadDir = '../assets/uploads/company-logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'company_' . $userId . '_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                $logoPath = 'assets/uploads/company-logos/' . $fileName;
            } else {
                $errors[] = 'Failed to upload logo.';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Update company profile
            $stmt = $pdo->prepare("
                UPDATE companies SET 
                    company_name = ?, industry = ?, founded_year = ?, 
                    company_size = ?, website = ?, description = ?, 
                    city = ?, state = ?, country = ?, address = ?, 
                    postal_code = ?, contact_phone = ?, contact_email = ?, 
                    linkedin_url = ?, twitter_url = ?, facebook_url = ?, 
                    instagram_url = ?, benefits = ?, culture = ?, 
                    mission = ?, vision = ?, updated_at = NOW()
                WHERE user_id = ? AND is_deleted = 0
            ");
            
            $stmt->execute([
                $companyName,
                $industry,
                clean($_POST['founded_year'] ?? ''),
                clean($_POST['company_size'] ?? ''),
                clean($_POST['website'] ?? ''),
                clean($_POST['description'] ?? ''),
                $city,
                $state,
                $country,
                clean($_POST['address'] ?? ''),
                clean($_POST['postal_code'] ?? ''),
                clean($_POST['contact_phone'] ?? ''),
                clean($_POST['contact_email'] ?? ''),
                clean($_POST['linkedin_url'] ?? ''),
                clean($_POST['twitter_url'] ?? ''),
                clean($_POST['facebook_url'] ?? ''),
                clean($_POST['instagram_url'] ?? ''),
                clean($_POST['benefits'] ?? ''),
                clean($_POST['culture'] ?? ''),
                clean($_POST['mission'] ?? ''),
                clean($_POST['vision'] ?? ''),
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

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company.css">

<div class="flex min-h-screen bg-gray-50">
  <main class="flex-1 p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Company Profile</h1>
      <p class="text-gray-600">Manage your company information and showcase your brand.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Form -->
      <div class="lg:col-span-2">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- Basic Information -->
          <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Basic Information</h2>
            
            <div class="space-y-6">
              <!-- Logo Upload -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
                <div class="flex items-center gap-6">
                  <div class="avatar" style="width: 80px; height: 80px; font-size: 32px;">
                    <?php 
                    if ($company['logo']): ?>
                      <img src="/apex-nexus-portal/<?php echo htmlspecialchars($company['logo']); ?>" 
                           alt="Company Logo" class="w-full h-full object-cover rounded-full">
                    <?php else: ?>
                      <?php echo strtoupper(substr($company['company_name'], 0, 1)); ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <input type="file" name="logo" accept="image/*" class="hidden" id="logoInput">
                    <button type="button" onclick="document.getElementById('logoInput').click()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                      Change Logo
                    </button>
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, GIF, WebP up to 5MB</p>
                  </div>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                  <input type="text" name="company_name" required
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['company_name']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Industry *</label>
                  <input type="text" name="industry" required
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['industry']); ?>">
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Founded Year</label>
                  <input type="number" name="founded_year" min="1900" max="<?php echo date('Y'); ?>"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['founded_year']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Size</label>
                  <select name="company_size" class="search-input">
                    <option value="">Select Size</option>
                    <option value="1-10" <?php echo $company['company_size'] === '1-10' ? 'selected' : ''; ?>>1-10</option>
                    <option value="11-50" <?php echo $company['company_size'] === '11-50' ? 'selected' : ''; ?>>11-50</option>
                    <option value="51-200" <?php echo $company['company_size'] === '51-200' ? 'selected' : ''; ?>>51-200</option>
                    <option value="201-500" <?php echo $company['company_size'] === '201-500' ? 'selected' : ''; ?>>201-500</option>
                    <option value="501-1000" <?php echo $company['company_size'] === '501-1000' ? 'selected' : ''; ?>>501-1000</option>
                    <option value="1000+" <?php echo $company['company_size'] === '1000+' ? 'selected' : ''; ?>>1000+</option>
                  </select>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                <input type="url" name="website"
                         class="search-input"
                         placeholder="https://www.example.com"
                         value="<?php echo htmlspecialchars($company['website']); ?>">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Description</label>
                <textarea name="description" rows="4"
                          class="search-input"
                          placeholder="Tell candidates about your company..."><?php echo htmlspecialchars($company['description']); ?></textarea>
              </div>
            </div>
          </div>

          <!-- Location Information -->
          <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Location Information</h2>
            
            <div class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                  <input type="text" name="city" required
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['city']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                  <input type="text" name="state"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['state']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                  <input type="text" name="country"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['country']); ?>">
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <input type="text" name="address"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['address']); ?>">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                <input type="text" name="postal_code"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['postal_code']); ?>">
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Contact Information</h2>
            
            <div class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                  <input type="tel" name="contact_phone"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['contact_phone']); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                  <input type="email" name="contact_email"
                         class="search-input"
                         value="<?php echo htmlspecialchars($company['contact_email']); ?>">
                </div>
              </div>
              
              <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Social Media Links</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <input type="url" name="linkedin_url"
                             class="search-input"
                             placeholder="LinkedIn URL"
                             value="<?php echo htmlspecialchars($company['linkedin_url']); ?>">
                  </div>
                  <div>
                    <input type="url" name="twitter_url"
                             class="search-input"
                             placeholder="Twitter URL"
                             value="<?php echo htmlspecialchars($company['twitter_url']); ?>">
                  </div>
                  <div>
                    <input type="url" name="facebook_url"
                             class="search-input"
                             placeholder="Facebook URL"
                             value="<?php echo htmlspecialchars($company['facebook_url']); ?>">
                  </div>
                  <div>
                    <input type="url" name="instagram_url"
                             class="search-input"
                             placeholder="Instagram URL"
                             value="<?php echo htmlspecialchars($company['instagram_url']); ?>">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Company Culture -->
          <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Company Culture</h2>
            
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Benefits & Perks</label>
                <textarea name="benefits" rows="4"
                          class="search-input"
                          placeholder="List the benefits and perks you offer to employees..."><?php echo htmlspecialchars($company['benefits']); ?></textarea>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Culture</label>
                <textarea name="culture" rows="4"
                          class="search-input"
                          placeholder="Describe your company culture and work environment..."><?php echo htmlspecialchars($company['culture']); ?></textarea>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Mission Statement</label>
                  <textarea name="mission" rows="3"
                            class="search-input"
                            placeholder="What is your company's mission?"><?php echo htmlspecialchars($company['mission']); ?></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Vision Statement</label>
                  <textarea name="vision" rows="3"
                            class="search-input"
                            placeholder="What is your company's vision?"><?php echo htmlspecialchars($company['vision']); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <a href="/apex-nexus-portal/company/dashboard.php" 
               class="px-6 py-2.5 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
              Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
              Save Changes
            </button>
          </div>
        </form>
      </div>

      <!-- Right Column - Preview -->
      <div class="lg:col-span-1">
        <div class="sticky top-24 space-y-6">
          <!-- Profile Preview -->
          <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile Preview</h3>
            
            <div class="text-center mb-6">
              <div class="avatar mx-auto mb-4" style="width: 80px; height: 80px; font-size: 32px;">
                <?php 
                if ($company['logo']): ?>
                  <img src="/apex-nexus-portal/<?php echo htmlspecialchars($company['logo']); ?>" 
                       alt="Company Logo" class="w-full h-full object-cover rounded-full">
                <?php else: ?>
                  <?php echo strtoupper(substr($company['company_name'], 0, 1)); ?>
                <?php endif; ?>
              </div>
              <h4 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($company['company_name']); ?></h4>
              <p class="text-gray-600"><?php echo htmlspecialchars($company['industry']); ?></p>
              <?php if ($company['company_size']): ?>
                <span class="tag tag-blue"><?php echo htmlspecialchars($company['company_size']); ?> employees</span>
              <?php endif; ?>
            </div>
            
            <div class="space-y-3 text-sm">
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
              
              <?php if ($company['website']): ?>
                <div class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                  </svg>
                  <a href="<?php echo htmlspecialchars($company['website']); ?>" 
                     target="_blank" class="text-blue-600 hover:text-blue-700">
                    Visit Website
                  </a>
                </div>
              <?php endif; ?>
              
              <?php if ($company['founded_year']): ?>
                <div class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  Founded <?php echo htmlspecialchars($company['founded_year']); ?>
                </div>
              <?php endif; ?>
            </div>
            
            <?php if ($company['description']): ?>
              <div class="mt-6 pt-6 border-t border-gray-200">
                <h5 class="font-medium text-gray-900 mb-2">About</h5>
                <p class="text-sm text-gray-600 line-clamp-3">
                  <?php echo htmlspecialchars($company['description']); ?>
                </p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Completion Status -->
          <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile Completion</h3>
            <div class="space-y-3">
              <?php
              $fields = [
                'company_name' => 'Company Name',
                'industry' => 'Industry',
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
            
            <div class="mt-6">
              <div class="flex justify-between text-sm text-gray-700 mb-2">
                <span>Completion</span>
                <span><?php echo round(($completed / $total) * 100); ?>%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($completed / $total) * 100; ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Quick Actions Floating Button -->
<div class="quick-actions">
    <button class="fab" onclick="toggleQuickActions()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
    </button>
    
    <div id="quickActionsMenu" class="quick-actions-menu hidden">
        <a href="/apex-nexus-portal/company/post-job.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 4v16m8-8H4"/>
            </svg>
            <span>Post Job</span>
        </a>
        <a href="/apex-nexus-portal/company/manage-jobs.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="9" x2="15" y2="9"/>
                <line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
            <span>Manage Jobs</span>
        </a>
        <a href="/apex-nexus-portal/company/applicants.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-4-4h-1v-4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v4h-1"/>
            </svg>
            <span>View Applicants</span>
        </a>
        <a href="/apex-nexus-portal/company/profile.php" class="quick-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Edit Profile</span>
        </a>
    </div>
</div>

<script>
function toggleQuickActions() {
    const menu = document.getElementById('quickActionsMenu');
    menu.classList.toggle('hidden');
    
    if (!menu.classList.contains('hidden')) {
        setTimeout(() => {
            document.addEventListener('click', closeQuickActions);
        }, 100);
    }
}

function closeQuickActions(e) {
    const menu = document.getElementById('quickActionsMenu');
    const button = document.querySelector('.fab');
    
    if (!menu.contains(e.target) && !button.contains(e.target)) {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeQuickActions);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>