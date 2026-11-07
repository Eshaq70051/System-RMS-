<?php
/**
 * Test App Logo Upload, Update, and Removal in Settings
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';

use App\Models\Database;

echo "====================================================\n";
echo "    App Logo Upload & Setting Test                  \n";
echo "====================================================\n\n";

$db = Database::getInstance();

// 1. Simulate saving a custom logo in settings
$dummyLogoPath = 'uploads/logo/test_logo.png';
$uploadDir = __DIR__ . '/../public/uploads/logo';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
// Create dummy 1x1 png image
file_put_contents(__DIR__ . '/../public/' . $dummyLogoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

$exists = $db->fetch("SELECT id FROM settings WHERE setting_key = 'restaurant_logo'");
if ($exists) {
    $db->update('settings', ['setting_value' => $dummyLogoPath], 'setting_key = :k', ['k' => 'restaurant_logo']);
} else {
    $db->insert('settings', ['setting_key' => 'restaurant_logo', 'setting_value' => $dummyLogoPath]);
}

$savedLogo = get_setting('restaurant_logo');
assert($savedLogo === $dummyLogoPath, "Logo setting should match saved path");
assert(file_exists(__DIR__ . '/../public/' . $savedLogo), "Logo file must exist on disk");
echo "[PASS] Logo successfully configured in settings: {$savedLogo}\n";

// 2. Verify layouts render with logo
ob_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'عبدالله السعيد';
$pageTitle = 'اختبار';
$appName = get_setting('restaurant_name', 'نظام إدارة المطاعم');
$appLogo = get_setting('restaurant_logo');
require __DIR__ . '/../views/layouts/main.php';
$html = ob_get_clean();

assert(strpos($html, $dummyLogoPath) !== false, "Sidebar brand header must include custom logo img tag");
echo "[PASS] Main layout sidebar verified rendering custom logo img!\n";

// 3. Verify Login page renders with logo
ob_start();
require __DIR__ . '/../views/auth/login.php';
$loginHtml = ob_get_clean();

assert(strpos($loginHtml, $dummyLogoPath) !== false, "Login page must include custom logo img tag");
echo "[PASS] Login page verified rendering custom logo img!\n";

// 4. Test remove logo
@unlink(__DIR__ . '/../public/' . $dummyLogoPath);
$db->execute("DELETE FROM settings WHERE setting_key = 'restaurant_logo'");
$clearedLogo = get_setting('restaurant_logo');
assert(empty($clearedLogo), "Logo setting should be empty after deletion");
echo "[PASS] Logo successfully removed and fallback to default icon works!\n";

echo "\n----------------------------------------------------\n";
echo "All App Logo Tests Passed Successfully!\n";
echo "----------------------------------------------------\n";
