<?php

use App\Models\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Base URL detection and generation
 */
function base_url(string $path = ''): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(dirname($scriptName), '/\\');
    
    // Normalize slashes
    $dir = str_replace('\\', '/', $dir);

    // If running in root
    $cleanPath = ltrim($path, '/');
    if ($dir === '' || $dir === '.') {
        return '/' . $cleanPath;
    }

    return $dir . ($cleanPath ? '/' . $cleanPath : '');
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function auth_check(): bool
{
    return !empty($_SESSION['user_id']);
}

function auth_user(): ?array
{
    if (!auth_check()) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

function has_role(string ...$roles): bool
{
    $user = auth_user();
    if (!$user) return false;
    $roleSlug = $user['role_slug'] ?? '';
    if ($roleSlug === 'admin') return true; // Admin has all rights
    return in_array($roleSlug, $roles, true);
}

function has_permission(string $permissionSlug): bool
{
    $user = auth_user();
    if (!$user) return false;
    if (($user['role_slug'] ?? '') === 'admin') return true;

    $perms = $_SESSION['user_permissions'] ?? [];
    return in_array($permissionSlug, $perms, true);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function get_setting(string $key, $default = ''): string
{
    static $settingsCache = null;
    if ($settingsCache === null) {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        $settingsCache = [];
        foreach ($rows as $r) {
            $settingsCache[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $settingsCache[$key] ?? (string)$default;
}

function currency_format($amount): string
{
    $symbol = get_setting('currency_symbol', 'ر.س');
    return number_format((float)$amount, 2) . ' ' . $symbol;
}

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function log_audit(string $action, string $module, string $details = ''): void
{
    try {
        $db = Database::getInstance();
        $userId = auth_user()['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $db->insert('audit_logs', [
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'ip_address' => $ip
        ]);
    } catch (Exception $e) {
        // Silently continue
    }
}

function get_active_shift(int $userId, int $branchId): ?array
{
    $db = Database::getInstance();
    return $db->fetch(
        "SELECT * FROM cashier_shifts WHERE branch_id = :b AND user_id = :u AND status = 'open' ORDER BY id DESC LIMIT 1",
        ['b' => $branchId, 'u' => $userId]
    );
}
