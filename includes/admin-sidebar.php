<?php
/**
 * Admin Sidebar Navigation
 */

// Get current page for active state detection
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPage = basename($currentPath, '.php');
?>

<!-- Admin Sidebar -->
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-800 text-white flex flex-col">
    <!-- Sidebar Header -->
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-white">Apex Nexus</div>
                <div class="text-xs text-slate-400">Recruitment Portal</div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">
        <!-- Main Menu -->
        <div>
            <h3 class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Main Menu</h3>
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="/apex-nexus-portal/admin/dashboard.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'dashboard' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    Dashboard
                </a>

                <!-- Jobs -->
                <a href="/apex-nexus-portal/admin/jobs.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'jobs' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    Jobs
                </a>

                <!-- Companies -->
                <a href="/apex-nexus-portal/admin/companies.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'companies' || $currentPage === 'company-detail' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                    Companies
                </a>

                <!-- Candidates -->
                <a href="/apex-nexus-portal/admin/candidates.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'candidates' || $currentPage === 'candidate-detail' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                    Candidates
                </a>

                <!-- Applications -->
                <a href="/apex-nexus-portal/admin/applications.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'applications' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Applications
                </a>
            </div>
        </div>

        <!-- Management -->
        <div>
            <h3 class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Management</h3>
            <div class="space-y-1">
                <!-- Settings -->
                <a href="/apex-nexus-portal/admin/settings.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'settings' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </a>

                <!-- My Profile -->
                <a href="/apex-nexus-portal/admin/profile.php"
                    class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPage === 'profile' ? 'bg-slate-900 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-slate-700">
        <div class="flex items-center space-x-3 mb-4">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-sm font-bold text-white">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 2)); ?>
                    </span>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </p>
                <p class="text-xs text-slate-400 truncate">
                    <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@example.com'); ?>
                </p>
            </div>
        </div>

        <a href="/apex-nexus-portal/logout.php" onclick="return confirm('Are you sure you want to logout?')"
            class="flex items-center px-3 py-2 text-sm font-medium text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-colors">

            <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>

            Logout
        </a>
    </div>
</div>

<!-- Mobile sidebar overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden"></div>