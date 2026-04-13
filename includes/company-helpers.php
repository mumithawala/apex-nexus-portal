<?php
/**
 * Helper functions for company dashboard
 */

// Calculate company profile completion percentage
function calculateCompanyProfileCompletion($company) {
    $fields = [
        'company_name', 'industry', 'city', 'state', 'country',
        'website', 'description', 'logo', 'contact_phone',
        'contact_email', 'linkedin_url', 'twitter_url', 'facebook_url',
        'instagram_url', 'benefits', 'culture', 'mission', 'vision',
        'founded_year', 'company_size', 'address', 'postal_code'
    ];
    
    $filled = 0;
    $total = count($fields);
    
    foreach ($fields as $field) {
        if (!empty($company[$field])) {
            $filled++;
        }
    }
    
    return round(($filled / $total) * 100);
}

// Time ago helper function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 3600) . ' hours ago';
    if ($diff < 86400) return floor($diff / 86400) . ' days ago';
    if ($diff < 31536000) return floor($diff / 31536000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}

?>
