<?php
require_once 'candidate-helpers.php';
$completion = calculateProfileCompletion($candidate);
$currentPage = basename($_SERVER['PHP_SELF']);
$initials = substr($candidate['full_name'] ?? 'U', 0, 1) . substr($candidate['full_name'] ?? 'User', -1);
?>

<!-- Mobile menu toggle button -->
<button data-drawer-target="candidate-sidebar" data-drawer-toggle="candidate-sidebar" aria-controls="candidate-sidebar" type="button" class="inline-flex items-center p-2 mt-2 ms-3 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
    <span class="sr-only">Open sidebar</span>
    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path clip-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z" fill-rule="evenodd"></path>
    </svg>
</button>

<!-- Sidebar -->
<aside id="candidate-sidebar" class="candidate-sidebar fixed top-0 left-0 z-40 w-64 h-screen bg-white border-r border-gray-200 lg:translate-x-0 transition-transform" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto">
        <!-- Profile Section -->
        <div class="mb-6 text-center">
            <div class="relative inline-flex items-center justify-center w-20 h-20 overflow-hidden bg-gray-100 rounded-full mb-3">
                <?php if (!empty($candidate['profile_photo'])): ?>
                    <img src="/apex-nexus-portal/<?php echo htmlspecialchars($candidate['profile_photo']); ?>" alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-medium text-gray-600"><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
            </div>
            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($candidate['full_name'] ?? 'User'); ?></h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Candidate
            </span>
        </div>

        <!-- Profile Completion -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Profile Completion</span>
                <span class="text-sm font-bold text-blue-600"><?php echo $completion; ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $completion; ?>%"></div>
            </div>
        </div>

        <!-- Navigation -->
        <ul class="space-y-2 font-medium">
            <li>
                <a href="/apex-nexus-portal/candidate/dashboard.php" 
                   class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/apex-nexus-portal/candidate/search-jobs.php" 
                   class="nav-link <?php echo $currentPage === 'search-jobs.php' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Search Jobs</span>
                </a>
            </li>
            <li>
                <a href="/apex-nexus-portal/candidate/my-applications.php" 
                   class="nav-link <?php echo $currentPage === 'my-applications.php' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100 4h2a1 1 0 100 2 2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span>My Applications</span>
                </a>
            </li>
            <li>
                <a href="/apex-nexus-portal/candidate/profile.php" 
                   class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="/apex-nexus-portal/candidate/upload-resume.php" 
                   class="nav-link <?php echo $currentPage === 'upload-resume.php' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Upload Resume</span>
                </a>
            </li>
        </ul>

        <!-- Logout -->
        <div class="absolute bottom-4 left-4 right-4">
            <a href="/apex-nexus-portal/logout.php" class="nav-link text-red-600 hover:bg-red-50">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>

<script>
// Initialize Flowbite drawer
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flowbite !== 'undefined' && flowbite.Drawer) {
        const drawerOptions = {
            placement: 'left',
            backdrop: true,
            bodyScrolling: false,
            edge: false,
            edgeOffset: '',
            backdropClasses: 'bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-30',
            onHide: () => {
                console.log('drawer has been hidden');
            },
            onShow: () => {
                console.log('drawer has been shown');
            },
            onToggle: () => {
                console.log('drawer has been toggled');
            }
        };

        const drawer = new flowbite.Drawer(document.getElementById('candidate-sidebar'), drawerOptions);
    }
});
</script>
