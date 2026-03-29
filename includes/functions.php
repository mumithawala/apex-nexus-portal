<?php
/**
 * Common utility functions for the recruitment portal
 */

/**
 * Sanitize input data
 * @param mixed $data
 * @return string
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to specified URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}


/**
 * Get current user's role
 * @return string|null
 */
function userRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Format date to readable format
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Soft delete record by setting is_deleted flag
 * @param PDO $pdo
 * @param string $table
 * @param int $id
 * @return bool
 */
function softDelete($pdo, $table, $id) {
    try {
        $sql = "UPDATE $table SET is_deleted = 1, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Soft delete error: " . $e->getMessage());
        return false;
    }
}

/**
 * Set flash message
 * @param string $type
 * @param string $message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and unset flash message
 * @return array|null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>