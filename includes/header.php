<?php 
if (!isset($base_path)) {
    $base_path = '';
}

require_once dirname(__DIR__) . '/includes/language.php'; 
?>
<nav>
    <div class="logo">
        <a href="<?php echo $base_path; ?>index.php">
            <?php echo __('site_name'); ?>
        </a>
    </div>
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="nav-links">
        <!-- Language Dropdown -->
        <div class="language-dropdown">
            <button class="language-btn">
                <?php echo $_SESSION['lang'] === 'en' ? 'Eng' : 'አማ'; ?>
                <span class="arrow">▼</span>
            </button>
            <div class="language-content">
                <a href="?lang=en" class="<?php echo $_SESSION['lang'] === 'en' ? 'active' : ''; ?>">English</a>
                <a href="?lang=am" class="<?php echo $_SESSION['lang'] === 'am' ? 'active' : ''; ?>">አማርኛ</a>
            </div>
        </div>

                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="<?php echo $base_path; ?>admin/index.php"><?php echo __('dashboard'); ?></a>
                <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $base_path; ?>my-bookings.php"><?php echo __('nav_bookings'); ?></a>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <span><?php echo __('nav_welcome'); ?>, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="<?php echo $base_path; ?>logout.php"><?php echo __('nav_logout'); ?></a>
            </div>
        <?php else: ?>
            <div class="auth-links">
                <a href="<?php echo $base_path; ?>login.php"><?php echo __('nav_login'); ?></a>
                <a href="<?php echo $base_path; ?>register.php"><?php echo __('nav_register'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<style>
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    position: relative;
    background-color: #1a2634;
    color: white;
}

.logo {
    font-size: 1.25rem;
    font-weight: bold;
}

.logo a {
    color: white;
    text-decoration: none;
    transition: opacity 0.3s;
}

.logo a:hover {
    opacity: 0.9;
}

.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    padding: 0.5rem;
}

.hamburger span {
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 2px 0;
    transition: 0.4s;
    display: block;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    transition: background-color 0.3s;
    border-radius: 4px;
}

.nav-links a:hover {
    background-color: #34495e;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.auth-links {
    display: flex;
    gap: 1rem;
}

@media screen and (max-width: 768px) {
    .hamburger {
        display: flex;
    }

    .nav-links {
        display: none;
        width: 100%;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #1a2634;
        padding: 1rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        flex-direction: column;
        gap: 0.5rem;
        z-index: 1000;
    }

    .nav-links.active {
        display: flex;
    }

    .nav-links a {
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 4px;
    }

    .nav-links a:hover {
        background-color: #2c3e50;
    }

    .user-info {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
        padding: 0.5rem 0;
    }

    .auth-links {
        flex-direction: column;
        width: 100%;
        gap: 0.5rem;
    }

    .user-info span {
        padding: 0.5rem 1rem;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }
}

/* Language Dropdown Styles */
.language-dropdown {
    position: relative;
    display: inline-block;
}

.language-btn {
    background-color: transparent;
    color: white;
    padding: 0.5rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.language-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.arrow {
    font-size: 0.8rem;
    transition: transform 0.3s;
}

.language-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #1a2634;
    min-width: 120px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border-radius: 4px;
    z-index: 1000;
}

.language-content a {
    color: white;
    padding: 0.75rem 1rem;
    text-decoration: none;
    display: block;
    font-size: 0.9rem;
}

.language-content a:hover {
    background-color: #34495e;
}

.language-content a.active {
    background-color: rgba(255, 255, 255, 0.1);
}

.language-dropdown:hover .language-content {
    display: none;
}

.language-dropdown:hover .arrow {
    transform: rotate(180deg);
}

/* Mobile Responsive Styles */
@media screen and (max-width: 768px) {
    .language-dropdown {
        width: 100%;
        margin: 0.5rem 0;
    }

    .language-btn {
        width: 100%;
        justify-content: space-between;
    }

    .language-content {
        position: static;
        width: 100%;
        box-shadow: none;
        margin-top: 0.25rem;
    }

    .language-content a {
        padding: 0.75rem 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const languageBtn = document.querySelector('.language-btn');
    const languageContent = document.querySelector('.language-content');
    let isDropdownOpen = false;

    // Hamburger menu toggle
    hamburger.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        this.classList.toggle('active');
    });

    // Language dropdown toggle
    languageBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        isDropdownOpen = !isDropdownOpen;
        languageContent.style.display = isDropdownOpen ? 'block' : 'none';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!languageBtn.contains(e.target) && !languageContent.contains(e.target)) {
            languageContent.style.display = 'none';
            isDropdownOpen = false;
        }
    });

    // Prevent closing when clicking inside dropdown
    languageContent.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>
                        