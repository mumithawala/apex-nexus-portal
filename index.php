<?php
$pageTitle = "Apex Nexus Recruitment - Find Top Talent";
require_once 'includes/urls.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// If already logged in, redirect to their dashboard
if (isLoggedIn()) {
    $role = userRole();
    switch ($role) {
        case 'admin':    header("Location: " . $ADMIN_URL . "/dashboard.php"); exit;
        case 'company':  header("Location: " . $COMPANY_URL . "/dashboard.php"); exit;
        case 'candidate': header("Location: " . $CANDIDATE_URL . "/dashboard.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/landing-navbar.php'; ?>
<?php require_once 'components/hero.php'; ?>
<section id="about" class="bg-white py-10">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
       

        <!-- Story Section -->
        <div class="relative mb-10">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-cyan-50 rounded-3xl"></div>
            
            <div class="relative p-8 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="space-y-6">
                    <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Founded in 2020
                    </div>
                    
                    <h3 class="text-4xl font-bold text-gray-900 leading-tight">
                       From  <span class="text-blue-600">Job Post <span class="text-gray-900">to</span> Offer Letter</span> — All in One Place
                    </h3>
                    
                    <div class="space-y-4">
                        <p class="text-lg text-gray-700 leading-relaxed">
                            Started by HR tech experts with a simple mission: make recruitment smarter and accessible for every company.
                        </p>
                        
                        <!-- Key Stats -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="text-2xl font-bold text-blue-600 mb-1">150+</div>
                                <div class="text-sm text-gray-600">Countries</div>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="text-2xl font-bold text-green-600 mb-1">50M+</div>
                                <div class="text-sm text-gray-600">Candidates</div>
                            </div>
                        </div>
                        
                        <p class="text-lg text-gray-700 leading-relaxed">
                            Today, we're the trusted partner for Fortune 500 companies and innovative startups, processing millions of applications annually.
                        </p>
                    </div>
                    
                    <!-- Trust Indicators -->
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Fortune 500 Trusted
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            AI-Powered
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="h-5 w-5 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            24/7 Support
                        </div>
                    </div>
                </div>
                
                <!-- Right Visual -->
                <div class="relative">
                    <!-- Main Image -->
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" 
                             alt="Modern office team collaboration" 
                             class="w-full h-96 object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent"></div>
                        
                        <!-- Floating Achievement Cards -->
                        <div class="absolute top-6 right-6 bg-white/95 backdrop-blur-sm rounded-xl shadow-xl p-4 transform rotate-3 hover:rotate-0 transition-transform duration-300">
                            <div class="text-3xl font-bold text-blue-600">2020</div>
                            <div class="text-sm text-gray-700 font-medium">Founded</div>
                        </div>
                        
                        <div class="absolute bottom-6 left-6 bg-white/95 backdrop-blur-sm rounded-xl shadow-xl p-4 transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                            <div class="text-3xl font-bold text-green-600">2.5K+</div>
                            <div class="text-sm text-gray-700 font-medium">Companies</div>
                        </div>
                    </div>
                    
                    <!-- Decorative Elements -->
                    <div class="absolute -top-4 -left-4 w-20 h-20 bg-blue-100 rounded-full opacity-60"></div>
                    <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-cyan-100 rounded-full opacity-60"></div>
                    
                    <!-- Floating Dots Pattern -->
                    <div class="absolute top-1/2 -left-8 transform -translate-y-1/2">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="w-2 h-2 bg-blue-300 rounded-full"></div>
                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       

     
    </div>
</section>

<?php require_once 'components/features.php'; ?>
<?php require_once 'components/how-it-works.php'; ?>
<?php require_once 'components/global-features.php'; ?>
<?php require_once 'components/usa-recruitment.php'; ?>
<?php require_once 'components/stats.php'; ?>
<?php require_once 'components/testimonials.php'; ?>
<?php require_once 'components/cta-banner.php'; ?>
<?php require_once 'includes/landing-footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<script src="<?php echo $BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>