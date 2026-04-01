<?php
/**
 * frontend/includes/navbar.php
 * Shared premium navbar — include at top of any page.
 *
 * Usage:
 *   $navActive = 'home'; // or 'fleet', 'cities', 'about', 'dashboard', 'admin'
 *   include __DIR__ . '/../includes/navbar.php';
 *
 * Required before include:
 *   require_once '../../backend/config/session.php';
 *   $user = getCurrentUser();
 */
$user = $user ?? getCurrentUser();
$navActive = $navActive ?? '';
?>
<nav class="navbar" id="navbar">
    <div class="nav-content">
        <a href="index.php" class="logo" id="logo-link">
            <div class="logo-icon">
                <i class="fas fa-car"></i>
            </div>
            SpinGo
        </a>

        <ul class="nav-links" id="nav-links">
            <li><a href="index.php"   <?= $navActive === 'home'   ? 'class="nav-active"' : '' ?>>Home</a></li>
            <li><a href="fleet.php"   <?= $navActive === 'fleet'  ? 'class="nav-active"' : '' ?>>Fleet</a></li>
            <li><a href="index.php#cities" <?= $navActive === 'cities' ? 'class="nav-active"' : '' ?>>Cities</a></li>
            <li><a href="index.php#about"  <?= $navActive === 'about'  ? 'class="nav-active"' : '' ?>>About</a></li>
            <?php if ($user): ?>
                <li><a href="dashboard.php" <?= $navActive === 'dashboard' ? 'class="nav-active"' : '' ?>>My Bookings</a></li>
            <?php endif; ?>
            <?php if ($user && $user['role'] === 'admin'): ?>
                <li><a href="admin.php" <?= $navActive === 'admin' ? 'class="nav-active"' : '' ?>>Admin</a></li>
            <?php endif; ?>
        </ul>

        <div class="auth-buttons">
            <?php if ($user): ?>
                <span class="user-greeting">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($user['name']) ?>
                </span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php"    class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>

        <button class="nav-hamburger" id="hamburger-btn" aria-label="Toggle navigation menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>
