<?php
$pageTitle = "About Apex Nexus Recruitment";
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// If already logged in, redirect to their dashboard
if (isLoggedIn()) {
    $role = userRole();
    switch ($role) {
        case 'admin':    header("Location: /apex-nexus-portal/admin/dashboard.php"); exit;
        case 'company':  header("Location: /apex-nexus-portal/company/dashboard.php"); exit;
        case 'candidate': header("Location: /apex-nexus-portal/candidate/dashboard.php"); exit;
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
    <link rel="stylesheet" href="/apex-nexus-portal/assets/css/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/landing-navbar.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-900 to-cyan-900 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-5xl lg:text-6xl font-extrabold text-white mb-6">
                About <span class="text-cyan-400">Apex Nexus</span>
            </h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto mb-8">
                Empowering companies worldwide to find exceptional talent through innovative recruitment technology
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/apex-nexus-portal/register.php" class="px-8 py-4 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                    Get Started Free
                </a>
                <a href="#contact" class="px-8 py-4 border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-blue-600 transition-colors">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'components/about-section.php'; ?>
<?php require_once 'components/contact-section.php'; ?>
<?php require_once 'includes/landing-footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<script src="/apex-nexus-portal/assets/js/main.js"></script>
</body>
</html>
