<?php
/**
 * Helper functions for candidate dashboard
 */

// Calculate profile completion percentage
function calculateProfileCompletion($candidate) {
    $fields = [
        'full_name', 'email', 'phone', 'city', 'state', 'country',
        'current_job_title', 'current_company', 'total_experience',
        'current_salary', 'expected_salary', 'skills', 'highest_qualification',
        'resume', 'profile_photo', 'linkedin_url', 'portfolio_url', 
        'notice_period', 'job_type', 'gender', 'date_of_birth',
        'nationality', 'preferred_location'
    ];
    
    $filled = 0;
    $total = count($fields);
    
    foreach ($fields as $field) {
        if (!empty($candidate[$field])) {
            $filled++;
        }
    }
    
    // Check for JSON fields
    if (!empty($candidate['experience'])) {
        $exp = json_decode($candidate['experience'], true);
        if (is_array($exp) && count($exp) > 0) {
            $filled++;
        }
    } else {
        $total++;
    }
    
    if (!empty($candidate['education'])) {
        $edu = json_decode($candidate['education'], true);
        if (is_array($edu) && count($edu) > 0) {
            $filled++;
        }
    } else {
        $total++;
    }
    
    return round(($filled / $total) * 100);
}

// Time ago helper function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}
?>
