<!-- Responsive Navigation Bar -->
<nav class="bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-700">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
        <!-- Brand -->
        <a href="index.php" class="flex items-center space-x-3 rtl:space-x-reverse">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">Recruitment Portal</span>
        </a>

        <!-- Mobile menu button -->
        <button data-collapse-toggle="navbar-dropdown" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-dropdown" aria-expanded="false">
            <span class="sr-only">Open main menu</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
            </svg>
        </button>

        <div class="hidden w-full md:block md:w-auto" id="navbar-dropdown">
            <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                
                <?php if (isLoggedIn()): ?>
                    <?php $role = userRole(); $currentUri = $_SERVER['REQUEST_URI']; ?>
                    
                    <?php if ($role === 'admin'): ?>
                        <!-- Admin Links -->
                        <li>
                            <a href="/admin/dashboard.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/admin/dashboard.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Dashboard</a>
                        </li>
                        <li>
                            <a href="/admin/jobs.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/admin/jobs.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Jobs</a>
                        </li>
                        <li>
                            <a href="/admin/companies.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/admin/companies.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Companies</a>
                        </li>
                        <li>
                            <a href="/admin/candidates.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/admin/candidates.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Candidates</a>
                        </li>
                        <li>
                            <a href="/admin/applications.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/admin/applications.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Applications</a>
                        </li>
                        
                    <?php elseif ($role === 'company'): ?>
                        <!-- Company Links -->
                        <li>
                            <a href="/company/dashboard.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/company/dashboard.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Dashboard</a>
                        </li>
                        <li>
                            <a href="/company/post-job.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/company/post-job.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Post Job</a>
                        </li>
                        <li>
                            <a href="/company/manage-jobs.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/company/manage-jobs.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">My Jobs</a>
                        </li>
                        <li>
                            <a href="/company/applicants.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/company/applicants.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Applicants</a>
                        </li>
                        <li>
                            <a href="/company/search-candidates.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/company/search-candidates.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Search Candidates</a>
                        </li>
                        
                    <?php elseif ($role === 'candidate'): ?>
                        <!-- Candidate Links -->
                        <li>
                            <a href="/candidate/dashboard.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/candidate/dashboard.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Dashboard</a>
                        </li>
                        <li>
                            <a href="/candidate/search-jobs.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/candidate/search-jobs.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">Search Jobs</a>
                        </li>
                        <li>
                            <a href="/candidate/my-applications.php" class="block py-2 px-3 rounded <?php echo strpos($currentUri, '/candidate/my-applications.php') !== false ? 'text-blue-700 bg-blue-100 md:bg-transparent md:text-blue-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700'; ?> md:p-0 dark:text-white md:dark:text-blue-500">My Applications</a>
                        </li>
                    <?php endif; ?>

                    <!-- User Dropdown -->
                    <li class="relative">
                        <button id="dropdownUserAvatarButton" data-dropdown-toggle="dropdownAvatar" class="flex items-center space-x-2 text-sm font-medium text-gray-900 hover:bg-gray-100 rounded-lg md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white dark:hover:bg-gray-700 dark:hover:text-blue-500 md:dark:hover:bg-transparent">
                            <?php
                            $userName = $_SESSION['name'] ?? 'User';
                            $initials = strtoupper(substr($userName, 0, 2));
                            ?>
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                <?php echo $initials; ?>
                            </div>
                            <span class="hidden md:block"><?php echo htmlspecialchars($userName); ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div id="dropdownAvatar" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600 absolute right-0 mt-2">
                            <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                <div class="font-semibold"><?php echo htmlspecialchars($userName); ?></div>
                                <div class="truncate"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                            </div>
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownUserAvatarButton">
                                <li>
                                    <a href="/<?php echo $role; ?>/profile.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Profile</a>
                                </li>
                                <li>
                                    <a href="/logout.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Logout</a>
                                </li>
                            </ul>
                        </div>
                    </li>

                <?php else: ?>
                    <!-- Not logged in -->
                    <li>
                        <a href="/login.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500">Login</a>
                    </li>
                    <li>
                        <a href="/register.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Initialize Flowbite dropdown -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dropdown functionality
        const dropdownButton = document.getElementById('dropdownUserAvatarButton');
        const dropdownMenu = document.getElementById('dropdownAvatar');
        
        if (dropdownButton && dropdownMenu) {
            dropdownButton.addEventListener('click', function() {
                dropdownMenu.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                    dropdownMenu.classList.add('hidden');
                }
            });
        }
    });
</script>