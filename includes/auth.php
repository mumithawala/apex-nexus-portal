<?php
/**
 * Authentication and session management
 */

// Start session on every page that includes this file
session_start();

/**
 * Redirect to login page if user is not logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: /apex-nexus-portal/login.php');
        exit();
    }
}

/**
 * Redirect to login page if user doesn't have the required role
 * @param string $role The required role (e.g., 'admin', 'recruiter', 'candidate')
 */
function requireRole($role) {
    // First check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: /apex-nexus-portal/login.php');
        exit();
    }
    
    // Check if user has the required role
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        // You could redirect to an unauthorized page instead
        header('Location: /apex-nexus-portal/login.php?error=unauthorized');
        exit();
    }
}

/**
 * Check if current user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user's role
 * @return string|null User role or null if not logged in
 */
function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

/**
 * Get current user's ID
 * @return int|null User ID or null if not logged in
 */
function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}
?>
