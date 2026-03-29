<?php
/**
 * Header file with HTML head section and flash messages
 */

// Require necessary files (auth.php first to handle session and functions)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Set default page title
$pageTitle = $pageTitle ?? 'Recruitment Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite CSS CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    
    <!-- Flowbite JS CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Display flash message if exists
$flash = getFlash();
if ($flash):
    $alertClass = match($flash['type']) {
        'success' => 'bg-green-100 border-green-400 text-green-700',
        'error' => 'bg-red-100 border-red-400 text-red-700',
        'info' => 'bg-blue-100 border-blue-400 text-blue-700',
        default => 'bg-gray-100 border-gray-400 text-gray-700'
    };
?>
<div id="flashAlert" class="border px-4 py-3 rounded relative <?php echo $alertClass; ?> mb-4" role="alert">
    <span class="block sm:inline"><?php echo htmlspecialchars($flash['message']); ?></span>
    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="document.getElementById('flashAlert').remove()">
        <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <title>Close</title>
            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
        </svg>
    </button>
</div>

<script>
// Auto dismiss flash message after 3 seconds
setTimeout(function() {
    const alert = document.getElementById('flashAlert');
    if (alert) {
        alert.remove();
    }
}, 3000);
</script>

<?php endif; ?>