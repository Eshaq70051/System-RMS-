<?php

namespace App\Controllers;

use App\Models\Database;

class SettingController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
        $settingsMap = [];
        foreach ($settings as $s) {
            $settingsMap[$s['setting_key']] = $s['setting_value'];
        }

        $branches = $this->db->fetchAll("SELECT * FROM branches ORDER BY id ASC");
        $users = $this->db->fetchAll(
            "SELECT u.*, r.name as role_name, b.name as branch_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             LEFT JOIN branches b ON u.branch_id = b.id 
             ORDER BY u.id ASC"
        );
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY id ASC");

        require_once __DIR__ . '/../../views/settings/index.php';
    }

    public function update(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('settings');
        }

        $keys = [
            'restaurant_name',
            'restaurant_phone',
            'restaurant_email',
            'restaurant_address',
            'tax_number',
            'commercial_registration',
            'currency',
            'currency_symbol',
            'tax_rate',
            'tax_enabled',
            'order_prefix',
            'invoice_footer',
            'auto_kitchen_print',
            'enable_recipe_deduction'
        ];

        foreach ($keys as $k) {
            $val = $_POST[$k] ?? '0';
            $exists = $this->db->fetch("SELECT id FROM settings WHERE setting_key = :k", ['k' => $k]);
            if ($exists) {
                $this->db->update('settings', ['setting_value' => $val, 'updated_at' => date('Y-m-d H:i:s')], 'setting_key = :k', ['k' => $k]);
            } else {
                $this->db->insert('settings', ['setting_key' => $k, 'setting_value' => $val]);
            }
        }

        // Handle Logo Removal
        if (!empty($_POST['remove_logo'])) {
            $currentLogo = $this->db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'restaurant_logo'");
            if ($currentLogo && file_exists(__DIR__ . '/../../public/' . $currentLogo)) {
                @unlink(__DIR__ . '/../../public/' . $currentLogo);
            }
            $this->db->execute("DELETE FROM settings WHERE setting_key = 'restaurant_logo'");
        }

        // Handle Logo Upload
        if (!empty($_FILES['restaurant_logo']['name']) && $_FILES['restaurant_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['restaurant_logo'];
            $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowedExts) && $file['size'] <= 3 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../public/uploads/logo';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destination = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Delete old logo file if exists
                    $oldLogo = $this->db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'restaurant_logo'");
                    if ($oldLogo && file_exists(__DIR__ . '/../../public/' . $oldLogo)) {
                        @unlink(__DIR__ . '/../../public/' . $oldLogo);
                    }

                    $relativeFilePath = 'uploads/logo/' . $fileName;
                    $exists = $this->db->fetch("SELECT id FROM settings WHERE setting_key = 'restaurant_logo'");
                    if ($exists) {
                        $this->db->update('settings', ['setting_value' => $relativeFilePath, 'updated_at' => date('Y-m-d H:i:s')], 'setting_key = :k', ['k' => 'restaurant_logo']);
                    } else {
                        $this->db->insert('settings', ['setting_key' => 'restaurant_logo', 'setting_value' => $relativeFilePath]);
                    }
                }
            } else {
                set_flash('error', 'صيغة ملف الشعار غير مدعومة أو حجم الملف يتجاوز 3 ميجابايت (المدعوم: PNG, JPG, WEBP, SVG)');
                redirect('settings');
            }
        }

        log_audit('تحديث الإعدادات', 'settings', "تحديث إعدادات النظام العامة");
        set_flash('success', 'تم حفظ الإعدادات العامة بنجاح');
        redirect('settings');
    }

    /**
     * User Management
     */
    public function storeUser(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('settings');
        }

        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);
        $branchId = (int)($_POST['branch_id'] ?? 1);

        if (empty($name) || empty($username) || empty($password) || !$roleId) {
            set_flash('error', 'يرجى ملء جميع الحقول المطلوبة للمستخدم');
            redirect('settings');
        }

        // Check unique username
        $exists = $this->db->fetch("SELECT id FROM users WHERE username = :u", ['u' => $username]);
        if ($exists) {
            set_flash('error', 'اسم المستخدم مسجل مسبقاً، اختر اسماً آخر');
            redirect('settings');
        }

        $this->db->insert('users', [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role_id' => $roleId,
            'branch_id' => $branchId,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        log_audit('إضافة مستخدم', 'users', "إنشاء مستخدم جديد {$username}");
        set_flash('success', 'تم إنشاء المستخدم بنجاح');
        redirect('settings');
    }

    /**
     * Audit Logs Viewer
     */
    public function auditLogs(): void
    {
        $logs = $this->db->fetchAll(
            "SELECT al.*, u.name as user_name, u.username 
             FROM audit_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.id DESC LIMIT 200"
        );
        require_once __DIR__ . '/../../views/settings/audit_logs.php';
    }

    /**
     * Download SQLite database backup
     */
    public function backup(): void
    {
        $dbPath = __DIR__ . '/../../database/restaurant.sqlite';
        if (!file_exists($dbPath)) {
            set_flash('error', 'ملف قاعدة البيانات غير متوفر');
            redirect('settings');
        }

        header('Content-Type: application/x-sqlite3');
        header('Content-Disposition: attachment; filename="restaurant_backup_' . date('Ymd_His') . '.sqlite"');
        header('Content-Length: ' . filesize($dbPath));
        readfile($dbPath);
        exit;
    }
}
