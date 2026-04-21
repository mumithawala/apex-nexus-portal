<?php
require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/auth.php';
?>
<nav class="sticky py-3 top-0 z-50 backdrop-blur-md bg-gray-900/95 border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?php echo $BASE_URL; ?>/" class="flex items-center space-x-2">
                    <!-- Briefcase SVG Icon -->
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-xl font-bold text-white">Apex Nexus</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center font-medium space-x-8">
                <!-- home -->
                <a href="<?php echo $BASE_URL; ?>/" class="text-gray-300 hover:text-white transition-colors duration-200">Home</a>
                <a href="<?php echo $BASE_URL; ?>/about.php" class="text-gray-300 hover:text-white transition-colors duration-200">About</a>
                <a href="#features" class="text-gray-300 hover:text-white transition-colors duration-200">Features</a>
                <a href="#how-it-works" class="text-gray-300 hover:text-white transition-colors duration-200">How It Works</a>
                <a href="#global-features" class="text-gray-300 hover:text-white transition-colors duration-200">Global</a>
                <a href="<?php echo $BASE_URL; ?>/contact.php" class="text-gray-300 hover:text-white transition-colors duration-200">Contact</a>
            </div>

            <!-- Right side buttons -->
            <div class="hidden md:flex items-center space-x-4">
                <?php if (isLoggedIn()): ?>
                    <?php 
                    $role = userRole();
                    $dashboardUrl = $role === 'admin' ? $ADMIN_URL : ($role === 'company' ? $COMPANY_URL : $CANDIDATE_URL);
                    ?>
                    <a href="<?php echo $dashboardUrl; ?>/dashboard.php" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo $BASE_URL; ?>/login.php" class="px-4 py-2 text-sm font-medium text-white border border-white rounded-lg hover:bg-white hover:text-gray-900 transition-colors duration-200">
                        Login
                    </a>
                    <a href="<?php echo $BASE_URL; ?>/register.php" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button data-collapse-toggle="mobile-menu" type="button" class="text-gray-300 hover:text-white p-2" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-gray-800 border-t border-gray-700">
        <div class="px-4 pt-2 pb-3 space-y-1">
            <a href="<?php echo $BASE_URL; ?>/" class="block px-3 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-md">Home</a>
            <a href="#features" class="block px-3 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-md">Features</a>
            <a href="#how-it-works" class="block px-3 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-md">How It Works</a>
            <a href="#global-features" class="block px-3 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-md">Global</a>
            <a href="<?php echo $BASE_URL; ?>/about.php" class="block px-3 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-md">About</a>
            <a href="<?php echo $BASE_URL; ?>/contact.php" class="block px-3 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-md">Contact</a>
            
            <?php if (isLoggedIn()): ?>
                <?php 
                $role = userRole();
                $dashboardUrl = $role === 'admin' ? $ADMIN_URL : ($role === 'company' ? $COMPANY_URL : $CANDIDATE_URL);
                ?>
                <a href="<?php echo $dashboardUrl; ?>/dashboard.php" class="block px-3 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-md mt-4">
                    Go to Dashboard
                </a>
            <?php else: ?>
                <a href="<?php echo $BASE_URL; ?>/login.php" class="block px-3 py-2 text-white border border-white rounded-md mt-4 text-center">
                    Login
                </a>
                <a href="<?php echo $BASE_URL; ?>/register.php" class="block px-3 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-md mt-2 text-center">
                    Get Started
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
