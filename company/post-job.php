<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Post a Job - Apex Nexus";
require_once '../includes/header.php';

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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company.css">

<div class="flex min-h-screen bg-gray-50">
  <main class="flex-1 p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Post a New Job</h1>
      <p class="text-gray-600">Create a new job posting to attract qualified candidates.</p>
    </div>

    <!-- Form -->
    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Form -->
      <div class="lg:col-span-2">
        <!-- Flowbite Tabs -->
        <div class="bg-white rounded-2xl border border-gray-100">
          <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600" data-tab="tab1">
                Job Basics
              </button>
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="tab2">
                Job Details
              </button>
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="tab3">
                Location & Salary
              </button>
            </nav>
          </div>

          <!-- Tab 1: Job Basics -->
          <div class="tab-content p-6" id="tab1">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Job Title *</label>
                <input type="text" name="title" required
                       class="search-input"
                       placeholder="e.g. Senior PHP Developer"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                  <select name="department_id" class="search-input">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                      <option value="<?php echo $dept['id']; ?>" 
                              <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                  <select name="category_id" class="search-input">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo $cat['id']; ?>"
                              <?php echo (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type *</label>
                  <select name="employment_type" required class="search-input">
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
                  <label class="block text-sm font-medium text-gray-700 mb-2">Work Mode *</label>
                  <select name="work_mode" required class="search-input">
                    <option value="">Select Mode</option>
                    <option value="on-site" <?php echo (($_POST['work_mode'] ?? '') == 'on-site') ? 'selected' : ''; ?>>On-site</option>
                    <option value="remote" <?php echo (($_POST['work_mode'] ?? '') == 'remote') ? 'selected' : ''; ?>>Remote</option>
                    <option value="hybrid" <?php echo (($_POST['work_mode'] ?? '') == 'hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Number of Openings</label>
                  <input type="number" name="openings" min="1" value="1"
                         class="search-input">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Application Deadline</label>
                  <input type="date" name="deadline"
                         class="search-input"
                         value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
                </div>
              </div>
            </div>

            <div class="flex justify-between mt-8">
              <button type="button" class="tab-nav-prev px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hidden">
                Previous
              </button>
              <button type="button" class="tab-nav-next ml-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" data-next="tab2">
                Next
              </button>
            </div>
          </div>

          <!-- Tab 2: Job Details -->
          <div class="tab-content p-6 hidden" id="tab2">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Job Description *</label>
                <textarea name="description" rows="5" required
                          class="search-input"
                          placeholder="Describe the role and responsibilities..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Key Responsibilities</label>
                <textarea name="responsibilities" rows="4"
                          class="search-input"
                          placeholder="List the key responsibilities for this role..."><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Requirements *</label>
                <textarea name="requirements" rows="4" required
                          class="search-input"
                          placeholder="Must-have qualifications and skills..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nice to Have</label>
                <textarea name="nice_to_have" rows="4"
                          class="search-input"
                          placeholder="Bonus skills that would be great..."><?php echo htmlspecialchars($_POST['nice_to_have'] ?? ''); ?></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Perks & Benefits</label>
                <textarea name="perks" rows="4"
                          class="search-input"
                          placeholder="What you offer to candidates..."><?php echo htmlspecialchars($_POST['perks'] ?? ''); ?></textarea>
              </div>
            </div>

            <div class="flex justify-between mt-8">
              <button type="button" class="tab-nav-prev px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50" data-prev="tab1">
                Previous
              </button>
              <button type="button" class="tab-nav-next ml-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" data-next="tab3">
                Next
              </button>
            </div>
          </div>

          <!-- Tab 3: Location & Salary -->
          <div class="tab-content p-6 hidden" id="tab3">
            <div class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                  <input type="text" name="city" required
                         class="search-input"
                         value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                  <input type="text" name="state"
                         class="search-input"
                         value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                  <input type="text" name="country" value="India"
                         class="search-input">
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location/Area</label>
                <input type="text" name="location"
                       class="search-input"
                       placeholder="e.g. SG Highway, Ahmedabad"
                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Salary Min (Optional)</label>
                  <input type="text" name="salary_min"
                           class="search-input"
                           placeholder="e.g. 500000"
                           value="<?php echo htmlspecialchars($_POST['salary_min'] ?? ''); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Salary Max (Optional)</label>
                  <input type="text" name="salary_max"
                           class="search-input"
                           placeholder="e.g. 800000"
                           value="<?php echo htmlspecialchars($_POST['salary_max'] ?? ''); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Salary Text</label>
                  <input type="text" name="salary"
                           class="search-input"
                           placeholder="e.g. 5-8 LPA"
                           value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>">
                </div>
              </div>

              <div class="flex items-center">
                <input type="checkbox" name="salary_visible" id="salary_visible" 
                       <?php echo isset($_POST['salary_visible']) ? 'checked' : ''; ?>>
                <label for="salary_visible" class="ml-2 text-sm text-gray-700">Show salary to candidates</label>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Experience Required</label>
                  <input type="text" name="experience_required"
                           class="search-input"
                           placeholder="e.g. 3-5 years"
                           value="<?php echo htmlspecialchars($_POST['experience_required'] ?? ''); ?>">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Education Required</label>
                  <input type="text" name="education"
                           class="search-input"
                           placeholder="e.g. B.Tech/BE"
                           value="<?php echo htmlspecialchars($_POST['education'] ?? ''); ?>">
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Apply Email</label>
                <input type="email" name="apply_email"
                         class="search-input"
                         placeholder="applications@company.com"
                         value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">External Apply URL (Optional)</label>
                <input type="url" name="apply_url"
                         class="search-input"
                         placeholder="https://careers.company.com"
                         value="<?php echo htmlspecialchars($_POST['apply_url'] ?? ''); ?>">
              </div>
            </div>

            <div class="flex justify-between mt-8">
              <button type="button" class="tab-nav-prev px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50" data-prev="tab2">
                Previous
              </button>
              <button type="submit" class="ml-auto px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Post Job
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Preview -->
      <div class="lg:col-span-1">
        <div class="sticky top-24">
          <div class="bg-white rounded-2xl border-2 border-dashed border-blue-200 p-5">
            <div class="text-sm font-medium text-blue-600 mb-4">Preview</div>
            
            <div id="jobPreview">
              <h3 class="text-lg font-semibold text-gray-900 mb-2" id="previewTitle">Job Title</h3>
              
              <div class="flex flex-wrap gap-2 mb-4">
                <span class="tag tag-blue" id="previewType">Employment Type</span>
                <span class="tag tag-green" id="previewMode">Work Mode</span>
              </div>
              
              <div class="space-y-2 text-sm text-gray-600">
                <div id="previewLocation">Location</div>
                <div id="previewSalary">Salary</div>
                <div id="previewDeadline">Deadline</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
// Tab navigation
document.querySelectorAll('.tab-button').forEach(button => {
  button.addEventListener('click', () => {
    const targetTab = button.dataset.tab;
    
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    
    // Show target tab
    document.getElementById(targetTab).classList.remove('hidden');
    
    // Update button states
    document.querySelectorAll('.tab-button').forEach(btn => {
      btn.classList.remove('border-blue-500', 'text-blue-600');
      btn.classList.add('border-transparent', 'text-gray-500');
    });
    button.classList.remove('border-transparent', 'text-gray-500');
    button.classList.add('border-blue-500', 'text-blue-600');
    
    // Update navigation buttons
    updateNavButtons(targetTab);
  });
});

// Tab navigation buttons
document.querySelectorAll('.tab-nav-next').forEach(button => {
  button.addEventListener('click', () => {
    const nextTab = button.dataset.next;
    document.querySelector(`[data-tab="${nextTab}"]`).click();
  });
});

document.querySelectorAll('.tab-nav-prev').forEach(button => {
  button.addEventListener('click', () => {
    const prevTab = button.dataset.prev;
    document.querySelector(`[data-tab="${prevTab}"]`).click();
  });
});

function updateNavButtons(currentTab) {
  // Hide all nav buttons
  document.querySelectorAll('.tab-nav-prev, .tab-nav-next').forEach(btn => btn.classList.add('hidden'));
  
  // Show appropriate buttons
  if (currentTab === 'tab1') {
    document.querySelector('[data-next="tab2"]').classList.remove('hidden');
  } else if (currentTab === 'tab2') {
    document.querySelector('[data-prev="tab1"]').classList.remove('hidden');
    document.querySelector('[data-next="tab3"]').classList.remove('hidden');
  } else if (currentTab === 'tab3') {
    document.querySelector('[data-prev="tab2"]').classList.remove('hidden');
  }
}

// Live preview update
function updatePreview() {
  const title = document.querySelector('[name="title"]').value || 'Job Title';
  const type = document.querySelector('[name="employment_type"]').value || 'Employment Type';
  const mode = document.querySelector('[name="work_mode"]').value || 'Work Mode';
  const city = document.querySelector('[name="city"]').value || 'Location';
  const salary = document.querySelector('[name="salary"]').value || 'Salary';
  const deadline = document.querySelector('[name="deadline"]').value || 'Deadline';
  
  document.getElementById('previewTitle').textContent = title;
  document.getElementById('previewType').textContent = type.charAt(0).toUpperCase() + type.slice(1);
  document.getElementById('previewMode').textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
  document.getElementById('previewLocation').textContent = city;
  document.getElementById('previewSalary').textContent = salary || 'Not specified';
  document.getElementById('previewDeadline').textContent = deadline ? new Date(deadline).toLocaleDateString() : 'Not specified';
}

// Add event listeners for live preview
document.querySelectorAll('input, select, textarea').forEach(input => {
  input.addEventListener('input', updatePreview);
});

// Initialize preview
updatePreview();
</script>

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