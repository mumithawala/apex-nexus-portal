<?php
require_once 'candidate-helpers.php';
$completion = calculateProfileCompletion($candidate);
$currentPage = basename($_SERVER['PHP_SELF']);
$initials = substr($candidate['full_name'] ?? 'U', 0, 1) . substr($candidate['full_name'] ?? 'User', -1);
?>

<!-- Modern Candidate Navigation Header -->
<nav class="candidate-nav">
    <div class="nav-container">
        <!-- Left Section - Logo & Brand -->
        <div class="nav-brand">
            <a href="/apex-nexus-portal/candidate/dashboard.php" class="brand-link">
                <div class="brand-logo">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                    <span class="brand-text">Apex Nexus</span>
                </div>
            </a>
        </div>

        <!-- Center Section - Main Navigation -->
        <div class="nav-menu">
            <a href="/apex-nexus-portal/candidate/dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>
            
            <a href="/apex-nexus-portal/candidate/search-jobs.php" class="nav-item <?php echo $currentPage === 'search-jobs.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <span>Find Jobs</span>
            </a>
            
            <a href="/apex-nexus-portal/candidate/my-applications.php" class="nav-item <?php echo $currentPage === 'my-applications.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>Applications</span>
            </a>
            
            <a href="/apex-nexus-portal/candidate/profile.php" class="nav-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Profile</span>
            </a>
        </div>

        <!-- Right Section - User Profile & Actions -->
        <div class="nav-actions">
            <!-- Notifications -->
            <button class="nav-btn notification-btn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notification-badge">3</span>
            </button>

            <!-- Messages -->
            <button class="nav-btn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </button>

            <!-- User Dropdown -->
            <div class="user-dropdown">
                <button class="user-menu-btn" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?php if (!empty($candidate['profile_photo'])): ?>
                            <img src="/apex-nexus-portal/<?php echo htmlspecialchars($candidate['profile_photo']); ?>" alt="Profile">
                        <?php else: ?>
                            <span class="avatar-text"><?php echo htmlspecialchars($initials); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($candidate['full_name'] ?? 'User'); ?></span>
                        <span class="user-role">Candidate</span>
                    </div>
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
                
                <div id="userMenu" class="user-menu hidden">
                    <div class="menu-header">
                        <div class="menu-avatar">
                            <?php if (!empty($candidate['profile_photo'])): ?>
                                <img src="/apex-nexus-portal/<?php echo htmlspecialchars($candidate['profile_photo']); ?>" alt="Profile">
                            <?php else: ?>
                                <span class="avatar-text"><?php echo htmlspecialchars($initials); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="menu-user-info">
                            <div class="menu-user-name"><?php echo htmlspecialchars($candidate['full_name'] ?? 'User'); ?></div>
                            <div class="menu-user-email"><?php echo htmlspecialchars($candidate['user_email'] ?? ''); ?></div>
                            <div class="profile-completion">
                                <div class="completion-bar">
                                    <div class="completion-fill" style="width: <?php echo $completion; ?>%"></div>
                                </div>
                                <span class="completion-text"><?php echo $completion; ?>% Complete</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="menu-divider"></div>
                    
                    <a href="/apex-nexus-portal/candidate/profile.php" class="menu-item">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        View Profile
                    </a>
                    
                    <a href="/apex-nexus-portal/candidate/upload-resume.php" class="menu-item">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                        Upload Resume
                    </a>
                    
                    <a href="/apex-nexus-portal/candidate/profile.php#settings" class="menu-item">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m4.22-13.22 4.24 4.24M1.54 8.96l4.24 4.24m12.44 0 4.24 4.24M1.54 15.04l4.24-4.24"/>
                        </svg>
                        Settings
                    </a>
                    
                    <div class="menu-divider"></div>
                    
                    <a href="/apex-nexus-portal/logout.php" class="menu-item logout">
                        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
</button>

<!-- Mobile Navigation Menu -->
<div id="mobileMenu" class="mobile-menu hidden">
    <div class="mobile-menu-header">
        <div class="mobile-brand">Apex Nexus</div>
        <button class="mobile-close" onclick="toggleMobileMenu()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    
    <div class="mobile-menu-items">
        <a href="/apex-nexus-portal/candidate/dashboard.php" class="mobile-nav-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
        
        <a href="/apex-nexus-portal/candidate/search-jobs.php" class="mobile-nav-item <?php echo $currentPage === 'search-jobs.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            Find Jobs
        </a>
        
        <a href="/apex-nexus-portal/candidate/my-applications.php" class="mobile-nav-item <?php echo $currentPage === 'my-applications.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Applications
        </a>
        
        <a href="/apex-nexus-portal/candidate/profile.php" class="mobile-nav-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            Profile
        </a>
    </div>
</div>

<script>
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden');
    
    // Close menu when clicking outside
    if (!menu.classList.contains('hidden')) {
        setTimeout(() => {
            document.addEventListener('click', closeUserMenu);
        }, 100);
    }
}

function closeUserMenu(e) {
    const menu = document.getElementById('userMenu');
    const button = document.querySelector('.user-menu-btn');
    
    if (!menu.contains(e.target) && !button.contains(e.target)) {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeUserMenu);
    }
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('hidden');
    
    // Prevent body scroll when menu is open
    if (!menu.classList.contains('hidden')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Close mobile menu when clicking on links
document.querySelectorAll('.mobile-nav-item').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.add('hidden');
        document.body.style.overflow = '';
    });
});
</script>
