<?php
require_once '../includes/auth.php';
require_once '../includes/company-helpers.php';
require_once '../includes/urls.php';
requireRole('company');
$pageTitle = "Applicant Details - Apex Nexus";
require_once '../includes/header.php';

$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get company record
$stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company['id'] ?? null;

// Get application ID and verify ownership
$applicationId = (int) ($_GET['id'] ?? 0);
if ($applicationId === 0) {
    setFlash('error', 'Invalid application ID');
    redirect('/apex-nexus-portal/company/applicants.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $newStatus = clean($_POST['status'] ?? '');
    
    if (in_array($newStatus, ['applied', 'reviewed', 'shortlisted', 'rejected'])) {
        try {
            // Verify application belongs to this company
            $stmt = $pdo->prepare("
                SELECT a.id FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                WHERE a.id = ? AND j.company_id = ? AND a.is_deleted = 0
            ");
            $stmt->execute([$applicationId, $companyId]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $applicationId]);
                setFlash('success', 'Application status updated successfully');
            }
        } catch (PDOException $e) {
            error_log("Status update error: " . $e->getMessage());
            setFlash('error', 'Failed to update status');
        }
    }
    redirect('/apex-nexus-portal/company/applicant-detail.php?id=' . $applicationId);
}

// Fetch application data with candidate and job details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.location as job_location, j.city as job_city, 
               j.state as job_state, j.employment_type, j.work_mode, j.salary,
               c.full_name, c.email, c.phone, c.city as candidate_city, c.state as candidate_state,
               c.current_job_title, c.current_company, c.total_experience, c.current_salary, 
               c.expected_salary, c.skills, c.highest_qualification, c.resume, c.profile_photo,
               c.linkedin_url, c.notice_period, c.job_type, c.gender, c.nationality,
               c.preferred_location
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN candidates c ON a.candidate_id = c.id
        WHERE a.id = ? AND j.company_id = ? AND a.is_deleted = 0 AND j.is_deleted = 0
    ");
    $stmt->execute([$applicationId, $companyId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        setFlash('error', 'Application not found or you do not have permission to view it');
        redirect('/apex-nexus-portal/company/applicants.php');
    }
    
    // Fetch candidate experience
    $stmt = $pdo->prepare("
        SELECT * FROM candidate_experience 
        WHERE candidate_id = ? 
        ORDER BY start_date DESC
    ");
    $stmt->execute([$application['candidate_id']]);
    $experience = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Application fetch error: " . $e->getMessage());
    setFlash('error', 'Failed to load application data');
    redirect('/apex-nexus-portal/company/applicants.php');
}
?>

<!-- Company CSS Imports -->
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-nav.css">
<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company-modern.css">

<!-- Modern Company Navigation -->
<?php include '../includes/company-navbar.php'; ?>

<div class="min-h-screen bg-gray-50 pt-28">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-2">
            Applicant Details
          </h1>
          <p class="text-gray-600">Review candidate profile and application information.</p>
        </div>
        <div class="flex gap-3">
          <a href="/apex-nexus-portal/company/applicants.php" 
             class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
            Back to Applicants
          </a>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Candidate Profile -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Candidate Header Card -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="flex items-start gap-6 relative z-10">
            <!-- Avatar -->
            <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full blur opacity-75 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
              <div class="relative w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full overflow-hidden shadow-xl flex items-center justify-center">
                <?php if ($application['profile_photo']): ?>
                  <img src="/apex-nexus-portal/<?php echo htmlspecialchars($application['profile_photo']); ?>" 
                       alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                  <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    <?php echo strtoupper(substr($application['full_name'], 0, 1)); ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Info -->
            <div class="flex-1">
              <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($application['full_name']); ?></h2>
              <div class="text-gray-600 mb-2">
                <?php if ($application['current_job_title']): ?>
                  <?php echo htmlspecialchars($application['current_job_title']); ?>
                  <?php if ($application['current_company']): ?>
                    @ <?php echo htmlspecialchars($application['current_company']); ?>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <div class="text-sm text-gray-500 mb-4">
                <?php if ($application['candidate_city']): ?>
                  <?php echo htmlspecialchars($application['candidate_city']); ?>
                  <?php if ($application['candidate_state']): ?>
                    , <?php echo htmlspecialchars($application['candidate_state']); ?>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              
              <!-- Tags -->
              <div class="flex flex-wrap gap-2 mb-4">
                <?php if ($application['total_experience']): ?>
                  <span class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs font-medium">
                    <?php echo $application['total_experience']; ?> yrs exp
                  </span>
                <?php endif; ?>
                <?php if ($application['notice_period']): ?>
                  <span class="inline-flex items-center px-2 py-1 rounded bg-green-50 text-green-700 text-xs font-medium">
                    <?php echo htmlspecialchars($application['notice_period']); ?> notice
                  </span>
                <?php endif; ?>
                <?php if ($application['job_type']): ?>
                  <span class="inline-flex items-center px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium">
                    <?php echo htmlspecialchars($application['job_type']); ?>
                  </span>
                <?php endif; ?>
              </div>
              
              <!-- Action Buttons -->
              <div class="flex flex-wrap gap-3">
                <?php if ($application['resume']): ?>
                  <a href="/apex-nexus-portal/assets/uploads/resumes/<?php echo htmlspecialchars($application['resume']); ?>" 
                     target="_blank" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
                    Download Resume
                  </a>
                <?php endif; ?>
                
                <?php if ($application['linkedin_url']): ?>
                  <a href="<?php echo htmlspecialchars($application['linkedin_url']); ?>" 
                     target="_blank" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    View LinkedIn
                  </a>
                <?php endif; ?>
                
                <!-- Status Update Form -->
                <form method="POST" class="inline">
                  <input type="hidden" name="action" value="update_status">
                  <select name="status" onchange="this.form.submit()" 
                          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">Change Status</option>
                    <option value="applied" <?php echo $application['status'] === 'applied' ? 'selected' : ''; ?>>Applied</option>
                    <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                    <option value="shortlisted" <?php echo $application['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                    <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                  </select>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="border-b border-gray-200 relative z-10">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600" data-tab="tab1">
                Overview
              </button>
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="tab2">
                Experience
              </button>
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="tab3">
                Education
              </button>
              <button type="button" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="tab4">
                Application
              </button>
            </nav>
          </div>

          <!-- Tab 1: Overview -->
          <div class="tab-content p-6" id="tab1">
            <div class="space-y-6">
              <!-- Skills -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Skills</h3>
                <div class="flex flex-wrap gap-2">
                  <?php 
                  $skills = array_filter(array_map('trim', explode(',', $application['skills'] ?? '')));
                  foreach ($skills as $skill): ?>
                    <span class="skill-chip"><?php echo htmlspecialchars($skill); ?></span>
                  <?php endforeach; ?>
                </div>
                <?php if (empty($skills)): ?>
                  <p class="text-gray-500">No skills listed</p>
                <?php endif; ?>
              </div>

              <!-- About -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">About</h3>
                <p class="text-gray-600">
                  <?php if (!empty($application['preferred_location'])): ?>
                    <strong>Preferred Location:</strong> <?php echo htmlspecialchars($application['preferred_location']); ?><br>
                  <?php endif; ?>
                  <?php if (!empty($application['nationality'])): ?>
                    <strong>Nationality:</strong> <?php echo htmlspecialchars($application['nationality']); ?><br>
                  <?php endif; ?>
                  <?php if (!empty($application['gender'])): ?>
                    <strong>Gender:</strong> <?php echo htmlspecialchars($application['gender']); ?><br>
                  <?php endif; ?>
                  <?php if (!empty($application['current_salary'])): ?>
                    <strong>Current Salary:</strong> <?php echo htmlspecialchars($application['current_salary']); ?><br>
                  <?php endif; ?>
                  <?php if (!empty($application['expected_salary'])): ?>
                    <strong>Expected Salary:</strong> <?php echo htmlspecialchars($application['expected_salary']); ?>
                  <?php endif; ?>
                </p>
              </div>
            </div>
          </div>

          <!-- Tab 2: Experience -->
          <div class="tab-content p-6 hidden" id="tab2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Work Experience</h3>
            <?php if (count($experience) > 0): ?>
              <div class="space-y-4">
                <?php foreach ($experience as $exp): ?>
                  <div class="border-l-4 border-blue-500 pl-4">
                    <div class="flex justify-between items-start mb-2">
                      <div>
                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($exp['job_title']); ?></h4>
                        <p class="text-gray-600"><?php echo htmlspecialchars($exp['company_name']); ?></p>
                      </div>
                      <span class="tag"><?php echo htmlspecialchars($exp['employment_type']); ?></span>
                    </div>
                    <p class="text-sm text-gray-500">
                      <?php echo formatDate($exp['start_date']); ?> - 
                      <?php echo $exp['is_current'] ? 'Present' : formatDate($exp['end_date']); ?>
                    </p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-gray-500">No work experience listed</p>
            <?php endif; ?>
          </div>

          <!-- Tab 3: Education -->
          <div class="tab-content p-6 hidden" id="tab3">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Education</h3>
            <div class="text-gray-600">
              <?php if (!empty($application['highest_qualification'])): ?>
                <p><strong>Highest Qualification:</strong> <?php echo htmlspecialchars($application['highest_qualification']); ?></p>
              <?php else: ?>
                <p>No education information listed</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Tab 4: Application -->
          <div class="tab-content p-6 hidden" id="tab4">
            <div class="space-y-6">
              <!-- Applied For -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Applied For</h3>
                <div class="text-gray-600">
                  <p><strong>Position:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
                  <p><strong>Location:</strong> <?php echo htmlspecialchars($application['job_location'] ?? $application['job_city']); ?></p>
                  <p><strong>Type:</strong> <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $application['employment_type']))); ?></p>
                  <p><strong>Mode:</strong> <?php echo htmlspecialchars(ucwords($application['work_mode'])); ?></p>
                  <?php if ($application['salary']): ?>
                    <p><strong>Salary:</strong> <?php echo htmlspecialchars($application['salary']); ?></p>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Application Details -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Application Details</h3>
                <div class="text-gray-600">
                  <p><strong>Applied Date:</strong> <?php echo formatDate($application['created_at']); ?></p>
                  <p><strong>Current Status:</strong> 
                    <span class="badge badge-<?php echo $application['status']; ?>">
                      <?php echo ucfirst($application['status']); ?>
                    </span>
                  </p>
                </div>
              </div>

              <!-- Cover Letter -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Cover Letter</h3>
                <div class="text-gray-600 whitespace-pre-wrap">
                  <?php if (!empty($application['cover_letter'])): ?>
                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                  <?php else: ?>
                    <p class="text-gray-500">No cover letter submitted</p>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Resume -->
              <?php if ($application['resume']): ?>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 mb-3">Resume</h3>
                  <a href="/apex-nexus-portal/assets/uploads/resumes/<?php echo htmlspecialchars($application['resume']); ?>" 
                     target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Resume
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Action Panel -->
      <div class="space-y-6">
        <!-- Application Status -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="flex items-center gap-3 mb-4 relative z-10">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Application Status</h3>
          </div>
          <div class="mb-4 relative z-10">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
              <?php 
              $statusColors = [
                'applied' => 'bg-blue-100 text-blue-700',
                'reviewed' => 'bg-yellow-100 text-yellow-700',
                'shortlisted' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700'
              ];
              echo $statusColors[$application['status']] ?? 'bg-gray-100 text-gray-700';
              ?>">
              <?php echo ucfirst($application['status']); ?>
            </span>
          </div>
          <form method="POST" class="relative z-10">
            <input type="hidden" name="action" value="update_status">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
              <option value="">Change Status</option>
              <option value="applied" <?php echo $application['status'] === 'applied' ? 'selected' : ''; ?>>Applied</option>
              <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
              <option value="shortlisted" <?php echo $application['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
              <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button type="submit" class="w-full mt-3 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg">
              Update Status
            </button>
          </form>
        </div>

        <!-- Candidate Summary -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="flex items-center gap-3 mb-4 relative z-10">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Candidate Summary</h3>
          </div>
          <div class="space-y-3 relative z-10">
            <?php if ($application['total_experience']): ?>
              <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
                <span class="text-gray-600 text-sm">Experience</span>
                <span class="font-semibold text-gray-900"><?php echo $application['total_experience']; ?> yrs</span>
              </div>
            <?php endif; ?>
            <?php if ($application['notice_period']): ?>
              <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
                <span class="text-gray-600 text-sm">Notice Period</span>
                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($application['notice_period']); ?></span>
              </div>
            <?php endif; ?>
            <?php if ($application['job_type']): ?>
              <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
                <span class="text-gray-600 text-sm">Job Type</span>
                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($application['job_type']); ?></span>
              </div>
            <?php endif; ?>
            <?php if ($application['expected_salary']): ?>
              <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
                <span class="text-gray-600 text-sm">Expected Salary</span>
                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($application['expected_salary']); ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Job Details -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="flex items-center gap-3 mb-4 relative z-10">
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-500 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Job Details</h3>
          </div>
          <div class="space-y-3 relative z-10">
            <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
              <span class="text-gray-600 text-sm">Position</span>
              <span class="font-semibold text-gray-900 text-right text-sm"><?php echo htmlspecialchars($application['job_title']); ?></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
              <span class="text-gray-600 text-sm">Location</span>
              <span class="font-semibold text-gray-900 text-right text-sm"><?php echo htmlspecialchars($application['job_location'] ?? $application['job_city']); ?></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
              <span class="text-gray-600 text-sm">Type</span>
              <span class="font-semibold text-gray-900 text-right text-sm"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $application['employment_type']))); ?></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
              <span class="text-gray-600 text-sm">Mode</span>
              <span class="font-semibold text-gray-900 text-right text-sm"><?php echo htmlspecialchars(ucwords($application['work_mode'])); ?></span>
            </div>
            <?php if ($application['salary']): ?>
              <div class="flex items-center justify-between p-3 bg-white/50 rounded-lg">
                <span class="text-gray-600 text-sm">Salary</span>
                <span class="font-semibold text-gray-900 text-right text-sm"><?php echo htmlspecialchars($application['salary']); ?></span>
              </div>
            <?php endif; ?>
          </div>
          <a href="/apex-nexus-portal/company/applicants.php?job_id=<?php echo $application['job_id']; ?>" 
             class="block w-full mt-4 text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors relative z-10">
            View All Applicants
          </a>
        </div>

        <!-- Candidate Contact -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="flex items-center gap-3 mb-4 relative z-10">
            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Contact Information</h3>
          </div>
          <div class="space-y-3 relative z-10">
            <?php if ($application['email']): ?>
              <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>" 
                 class="flex items-center gap-3 p-3 bg-white/50 rounded-lg hover:bg-white/70 transition-colors group">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                  </svg>
                </div>
                <span class="text-gray-700 text-sm truncate"><?php echo htmlspecialchars($application['email']); ?></span>
              </a>
            <?php endif; ?>
            
            <?php if ($application['phone']): ?>
              <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>" 
                 class="flex items-center gap-3 p-3 bg-white/50 rounded-lg hover:bg-white/70 transition-colors group">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                  </svg>
                </div>
                <span class="text-gray-700 text-sm"><?php echo htmlspecialchars($application['phone']); ?></span>
              </a>
            <?php endif; ?>
            
            <?php if ($application['linkedin_url']): ?>
              <a href="<?php echo htmlspecialchars($application['linkedin_url']); ?>" 
                 target="_blank" class="flex items-center gap-3 p-3 bg-white/50 rounded-lg hover:bg-white/70 transition-colors group">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                  <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                  </svg>
                </div>
                <span class="text-gray-700 text-sm">LinkedIn Profile</span>
              </a>
            <?php endif; ?>
          </div>
          
          <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>" 
             class="block w-full mt-4 text-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg relative z-10">
            Send Email
          </a>
        </div>

        <!-- Quick Notes -->
        <div class="bg-gradient-to-br from-white via-blue-50/30 to-purple-50/20 rounded-2xl shadow-xl border border-gray-100 backdrop-blur-sm p-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
          <div class="flex items-center gap-3 mb-4 relative z-10">
            <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Quick Notes</h3>
          </div>
          <textarea id="quickNotes" rows="4" placeholder="Add internal notes about this candidate..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none relative z-10"></textarea>
          <button onclick="saveQuickNotes()" 
                  class="w-full mt-3 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors relative z-10">
            Save Note
          </button>
        </div>
      </div>
    </div>
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
  });
});

// Quick Notes (localStorage)
const quickNotesTextarea = document.getElementById('quickNotes');
const notesKey = 'applicant_notes_<?php echo $applicationId; ?>';

// Load saved notes
quickNotesTextarea.value = localStorage.getItem(notesKey) || '';

// Save notes
function saveQuickNotes() {
  const notes = quickNotesTextarea.value.trim();
  if (notes) {
    localStorage.setItem(notesKey, notes);
    alert('Notes saved successfully!');
  } else {
    localStorage.removeItem(notesKey);
    alert('Notes cleared!');
  }
}

// Auto-save notes
quickNotesTextarea.addEventListener('input', () => {
  clearTimeout(quickNotesTextarea.saveTimeout);
  quickNotesTextarea.saveTimeout = setTimeout(() => {
    localStorage.setItem(notesKey, quickNotesTextarea.value);
  }, 1000);
});
</script>

<?php require_once '../includes/footer.php'; ?>