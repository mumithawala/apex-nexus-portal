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

<link rel="stylesheet" href="/apex-nexus-portal/assets/css/company.css">

<div class="flex min-h-screen bg-gray-50">
  <main class="flex-1 p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Applicant Details</h1>
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
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <div class="flex items-start gap-6">
            <!-- Avatar -->
            <div class="avatar" style="width: 64px; height: 64px; font-size: 24px;">
              <?php echo strtoupper(substr($application['full_name'], 0, 1)); ?>
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
                  <span class="tag"><?php echo $application['total_experience']; ?> yrs exp</span>
                <?php endif; ?>
                <?php if ($application['notice_period']): ?>
                  <span class="tag"><?php echo htmlspecialchars($application['notice_period']); ?> notice</span>
                <?php endif; ?>
                <?php if ($application['job_type']): ?>
                  <span class="tag"><?php echo htmlspecialchars($application['job_type']); ?></span>
                <?php endif; ?>
              </div>
              
              <!-- Action Buttons -->
              <div class="flex flex-wrap gap-3">
                <?php if ($application['resume']): ?>
                  <a href="/apex-nexus-portal/assets/uploads/resumes/<?php echo htmlspecialchars($application['resume']); ?>" 
                     target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
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
                          class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
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
        <div class="bg-white rounded-2xl border border-gray-100">
          <div class="border-b border-gray-200">
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
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Status</h3>
          <div class="mb-4">
            <span class="badge badge-<?php echo $application['status']; ?> text-lg px-4 py-2">
              <?php echo ucfirst($application['status']); ?>
            </span>
          </div>
          <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700">
              <option value="">Change Status</option>
              <option value="applied" <?php echo $application['status'] === 'applied' ? 'selected' : ''; ?>>Applied</option>
              <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
              <option value="shortlisted" <?php echo $application['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
              <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button type="submit" class="w-full mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Update Status
            </button>
          </form>
        </div>

        <!-- Job Details -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Job Details</h3>
          <div class="space-y-2 text-gray-600">
            <p><strong>Position:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($application['job_location'] ?? $application['job_city']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $application['employment_type']))); ?></p>
            <p><strong>Mode:</strong> <?php echo htmlspecialchars(ucwords($application['work_mode'])); ?></p>
            <?php if ($application['salary']): ?>
              <p><strong>Salary:</strong> <?php echo htmlspecialchars($application['salary']); ?></p>
            <?php endif; ?>
          </div>
          <a href="/apex-nexus-portal/company/applicants.php?job_id=<?php echo $application['job_id']; ?>" 
             class="block w-full mt-4 text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
            View All Applicants
          </a>
        </div>

        <!-- Candidate Contact -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
          <div class="space-y-3">
            <?php if ($application['email']): ?>
              <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>" 
                 class="block text-blue-600 hover:text-blue-700">
                <?php echo htmlspecialchars($application['email']); ?>
              </a>
            <?php endif; ?>
            
            <?php if ($application['phone']): ?>
              <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>" 
                 class="block text-gray-700 hover:text-gray-900">
                <?php echo htmlspecialchars($application['phone']); ?>
              </a>
            <?php endif; ?>
            
            <?php if ($application['linkedin_url']): ?>
              <a href="<?php echo htmlspecialchars($application['linkedin_url']); ?>" 
                 target="_blank" class="block text-blue-600 hover:text-blue-700">
                LinkedIn Profile
              </a>
            <?php endif; ?>
          </div>
          
          <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>" 
             class="block w-full mt-4 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            Send Email
          </a>
        </div>

        <!-- Quick Notes -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Notes</h3>
          <textarea id="quickNotes" rows="4" placeholder="Add internal notes about this candidate..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 resize-none"></textarea>
          <button onclick="saveQuickNotes()" 
                  class="w-full mt-3 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            Save Note
          </button>
        </div>
      </div>
    </div>

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