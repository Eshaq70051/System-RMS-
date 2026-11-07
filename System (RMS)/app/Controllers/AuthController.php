<?php

namespace App\Controllers;

use App\Models\Database;

class AuthController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function showLogin(): void
    {
        if (auth_check()) {
            redirect('');
        }
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى.');
            redirect('login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            set_flash('error', 'يرجى إدخال اسم المستخدم وكلمة المرور.');
            redirect('login');
        }

        $user = $this->db->fetch(
            "SELECT u.*, r.slug as role_slug, r.name as role_name, b.name as branch_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             LEFT JOIN branches b ON u.branch_id = b.id 
             WHERE (u.username = :u OR u.email = :u) AND u.is_active = 1",
            ['u' => $username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            set_flash('error', 'بيانات تسجيل الدخول غير صحيحة أو الحساب معطل.');
            redirect('login');
        }

        // Fetch user permissions
        $perms = $this->db->fetchAll(
            "SELECT p.slug FROM role_permissions rp 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE rp.role_id = :rid",
            ['rid' => $user['role_id']]
        );
        $permSlugs = array_column($perms, 'slug');

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'role_slug' => $user['role_slug'],
            'role_name' => $user['role_name'],
            'branch_id' => $user['branch_id'] ?? 1,
            'branch_name' => $user['branch_name'] ?? 'الفرع الرئيسي'
        ];
        $_SESSION['user_permissions'] = $permSlugs;

        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

        log_audit('تسجيل دخول', 'auth', "تسجيل دخول المستخدم {$user['username']}");

        set_flash('success', "أهلاً بك مجدداً يا {$user['name']}!");

        // If cashier, redirect to POS directly
        if ($user['role_slug'] === 'cashier') {
            redirect('pos');
        } elseif ($user['role_slug'] === 'kitchen') {
            redirect('kds');
        } else {
            redirect('');
        }
    }

    public function logout(): void
    {
        if (auth_check()) {
            log_audit('تسجيل خروج', 'auth', "تسجيل خروج المستخدم " . (auth_user()['username'] ?? ''));
        }
        session_destroy();
        redirect('login');
    }
}
