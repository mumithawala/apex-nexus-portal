<?php
/**
 * Simple URL Configuration
 * Local and Live URL Variables
 */

// Environment Detection
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
             strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || 
             strpos($_SERVER['HTTP_HOST'], '.local') !== false);

// Base URLs
if ($is_local) {
    // Local URLs
    $BASE_URL = 'http://localhost/apex-nexus-portal';
    $ASSETS_URL = 'http://localhost/apex-nexus-portal/assets';
    $UPLOADS_URL = 'http://localhost/apex-nexus-portal/assets/uploads';
    $CANDIDATE_URL = 'http://localhost/apex-nexus-portal/candidate';
    $ADMIN_URL = 'http://localhost/apex-nexus-portal/admin';
    $COMPANY_URL = 'http://localhost/apex-nexus-portal/company';
    
} else {
    // Live URLs (update with your live domain)
    $BASE_URL = 'https://ufdigitech.com/client-site/apex-nuxus-app';
    $ASSETS_URL = 'https://ufdigitech.com/client-site/apex-nuxus-app/assets';
    $UPLOADS_URL = 'https://ufdigitech.com/client-site/apex-nuxus-app/assets/uploads';
    $CANDIDATE_URL = 'https://ufdigitech.com/client-site/apex-nuxus-app/candidate';
    $ADMIN_URL = 'https://ufdigitech.com/client-site/apex-nuxus-app/admin';
    $COMPANY_URL = 'https://ufdigitech.com/client-site/apex-nuxus-app/company';
}

// Environment identifier for debugging
$ENVIRONMENT = $is_local ? 'LOCAL' : 'LIVE';

?>
