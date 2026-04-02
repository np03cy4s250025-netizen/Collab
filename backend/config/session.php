<?php
// backend/config/session.php — Centralized session management with CSRF & security hardening

if (session_status() === PHP_SESSION_NONE) {
    // Secure session cookie settings
    session_set_cookie_params([
        'lifetime' => 1800,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── CSRF Token ───────────────────────────────────────────────────────────────

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!$expected || !hash_equals($expected, $submitted)) {
        // Do NOT rotate token here — user needs to be able to go back
        // and resubmit the same page without regenerating a fresh token.
        $_SESSION['flash_error'] = 'Your session expired. Please try again.';
        $back = $_SERVER['HTTP_REFERER'] ?? (dirname($_SERVER['PHP_SELF']) . '/login.php');
        header('Location: ' . $back);
        exit;
    }
    // Token is valid — do NOT rotate here so back-button + resubmit still works
}

// ─── Auth Helpers ─────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(string $redirect = '../pages/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireAdmin(string $redirect = '../pages/login.php'): void {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: $redirect");
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['role']        ?? 'user',
    ];
}

// ─── Rate Limiting (session-based) ───────────────────────────────────────────

function rateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $now = time();
    $attempts = $_SESSION["rl_{$key}"] ?? [];
    // Remove old attempts outside the window
    $attempts = array_filter($attempts, fn($t) => ($now - $t) < $windowSeconds);
    if (count($attempts) >= $maxAttempts) {
        return false; // Rate limited
    }
    $attempts[] = $now;
    $_SESSION["rl_{$key}"] = array_values($attempts);
    return true;
}

function clearRateLimit(string $key): void {
    unset($_SESSION["rl_{$key}"]);
}
?>
