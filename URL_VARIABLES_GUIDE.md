# Simple URL Variables Guide

## Overview
Simple URL configuration with local and live variables that you can import in each file.

## Files Created

### `includes/urls.php`
Contains URL variables that automatically detect local vs live environment.

## Usage Instructions

### Step 1: Include the URL file
Add this line to the top of your PHP files:
```php
<?php
require_once '../includes/urls.php';
?>
```

### Step 2: Use the URL variables

#### Available Variables:
```php
$BASE_URL         // Base URL (http://localhost/apex-nexus-portal or https://your-domain.com)
$ASSETS_URL       // Assets URL (http://localhost/apex-nexus-portal/assets)
$UPLOADS_URL      // Uploads URL (http://localhost/apex-nexus-portal/assets/uploads)
$CANDIDATE_URL    // Candidate section URL (http://localhost/apex-nexus-portal/candidate)
$ADMIN_URL        // Admin section URL (http://localhost/apex-nexus-portal/admin)
$COMPANY_URL      // Company section URL (http://localhost/apex-nexus-portal/company)
```

#### Examples:

**CSS Files:**
```php
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/style.css">
```

**JavaScript Files:**
```php
<script src="<?php echo $ASSETS_URL; ?>/js/script.js"></script>
```

**Images:**
```php
<img src="<?php echo $ASSETS_URL; ?>/images/logo.png" alt="Logo">
```

**Uploaded Files:**
```php
<img src="<?php echo $UPLOADS_URL; ?>/resume.pdf" alt="Resume">
```

**Candidate Links:**
```php
<a href="<?php echo $CANDIDATE_URL; ?>/dashboard.php">Dashboard</a>
<a href="<?php echo $CANDIDATE_URL; ?>/profile.php">Profile</a>
```

**Admin Links:**
```php
<a href="<?php echo $ADMIN_URL; ?>/dashboard.php">Admin Dashboard</a>
```

**Company Links:**
```php
<a href="<?php echo $COMPANY_URL; ?>/dashboard.php">Company Dashboard</a>
```

## Environment Detection

The system automatically detects:
- **Local**: localhost, 127.0.0.1, or .local domains
- **Live**: Everything else

## Configuration

### Local URLs (in `includes/urls.php`):
```php
$BASE_URL = 'http://localhost/apex-nexus-portal';
$ASSETS_URL = 'http://localhost/apex-nexus-portal/assets';
// etc...
```

### Live URLs (update these in `includes/urls.php`):
```php
$BASE_URL = 'https://your-domain.com';
$ASSETS_URL = 'https://your-domain.com/assets';
// etc...
```

## Complete File Example

```php
<?php
require_once '../includes/auth.php';
require_once '../includes/urls.php';
requireRole('candidate');
?>

<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/style.css">
<link rel="stylesheet" href="<?php echo $ASSETS_URL; ?>/css/candidate.css">

<div class="dashboard">
    <h1>Welcome to Dashboard</h1>
    <a href="<?php echo $CANDIDATE_URL; ?>/profile.php">Edit Profile</a>
    <a href="<?php echo $CANDIDATE_URL; ?>/search-jobs.php">Search Jobs</a>
    <img src="<?php echo $ASSETS_URL; ?>/images/logo.png" alt="Logo">
</div>
```

## Benefits

1. **Simple to use** - Just include and use variables
2. **Automatic switching** - No manual changes needed
3. **Works everywhere** - Local and live environments
4. **Easy to maintain** - One file to update
5. **No function calls** - Direct variable access

This is the simplest way to manage URLs across environments!
