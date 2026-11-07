<?php

namespace App\Controllers;

use App\Models\Database;

class ProductController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $categoryId = $_GET['category_id'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $where = ["p.is_active = 1"];
        $params = [];

        if ($categoryId !== '') {
            $where[] = "p.category_id = :cid";
            $params['cid'] = (int)$categoryId;
        }

        if ($search !== '') {
            $where[] = "(p.name LIKE :search OR p.barcode LIKE :search OR p.sku LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);

        $products = $this->db->fetchAll(
            "SELECT p.*, c.name as category_name, c.color as category_color,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variants_count,
                    (SELECT COUNT(*) FROM product_addons WHERE product_id = p.id) as addons_count,
                    (SELECT COUNT(*) FROM recipes WHERE product_id = p.id) as recipe_exists 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE {$whereSql} 
             ORDER BY p.id DESC",
            $params
        );

        $categories = $this->db->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");

        require_once __DIR__ . '/../../views/products/index.php';
    }

    public function create(): void
    {
        $categories = $this->db->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
        require_once __DIR__ . '/../../views/products/create.php';
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('products/create');
        }

        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $costPrice = (float)($_POST['cost_price'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '') ?: ('BC' . rand(100000, 999999));
        $sku = trim($_POST['sku'] ?? '') ?: ('PRD-' . rand(1000, 9999));
        $unit = trim($_POST['unit'] ?? 'وجبة');
        $description = trim($_POST['description'] ?? '');
        $hasRecipe = isset($_POST['has_recipe']) ? 1 : 0;
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if (empty($name) || !$categoryId || $price <= 0) {
            set_flash('error', 'يرجى إدخال اسم الوجبة والقسم وسعر البيع بشكل صحيح');
            redirect('products/create');
        }

        // Handle Image Upload if any
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $imageName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $target = __DIR__ . '/../../public/uploads/products/' . $imageName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $imagePath = 'uploads/products/' . $imageName;
                }
            }
        }

        $prodId = $this->db->insert('products', [
            'category_id' => $categoryId,
            'name' => $name,
            'barcode' => $barcode,
            'sku' => $sku,
            'description' => $description,
            'price' => $price,
            'cost_price' => $costPrice,
            'unit' => $unit,
            'image' => $imagePath,
            'is_available' => $isAvailable,
            'is_active' => 1,
            'has_recipe' => $hasRecipe,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        log_audit('إضافة منتج', 'products', "إضافة الوجبة {$name} بسعر {$price} ر.س");
        set_flash('success', 'تمت إضافة المنتج بنجاح');
        redirect('products');
    }

    public function edit(int $id): void
    {
        $product = $this->db->fetch("SELECT * FROM products WHERE id = :id", ['id' => $id]);
        if (!$product) {
            set_flash('error', 'المنتج غير موجود');
            redirect('products');
        }

        $categories = $this->db->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
        $variants = $this->db->fetchAll("SELECT * FROM product_variants WHERE product_id = :id", ['id' => $id]);
        $addons = $this->db->fetchAll("SELECT * FROM product_addons WHERE product_id = :id", ['id' => $id]);

        require_once __DIR__ . '/../../views/products/edit.php';
    }

    public function update(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect("products/{$id}/edit");
        }

        $product = $this->db->fetch("SELECT * FROM products WHERE id = :id", ['id' => $id]);
        if (!$product) {
            set_flash('error', 'المنتج غير موجود');
            redirect('products');
        }

        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $costPrice = (float)($_POST['cost_price'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '') ?: $product['barcode'];
        $sku = trim($_POST['sku'] ?? '') ?: $product['sku'];
        $unit = trim($_POST['unit'] ?? 'وجبة');
        $description = trim($_POST['description'] ?? '');
        $hasRecipe = isset($_POST['has_recipe']) ? 1 : 0;
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        $updateData = [
            'category_id' => $categoryId,
            'name' => $name,
            'barcode' => $barcode,
            'sku' => $sku,
            'description' => $description,
            'price' => $price,
            'cost_price' => $costPrice,
            'unit' => $unit,
            'is_available' => $isAvailable,
            'has_recipe' => $hasRecipe,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Handle Image Upload
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $imageName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $target = __DIR__ . '/../../public/uploads/products/' . $imageName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $updateData['image'] = 'uploads/products/' . $imageName;
                }
            }
        }

        $this->db->update('products', $updateData, 'id = :id', ['id' => $id]);

        log_audit('تعديل منتج', 'products', "تعديل بيانات المنتج {$name}");
        set_flash('success', 'تم تحديث بيانات المنتج بنجاح');
        redirect('products');
    }

    public function delete(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('products');
        }

        // Soft delete: mark inactive
        $this->db->update('products', ['is_active' => 0], 'id = :id', ['id' => $id]);
        log_audit('حذف منتج', 'products', "تعطيل المنتج رقم #{$id}");
        set_flash('success', 'تم حذف / تعطيل المنتج بنجاح');
        redirect('products');
    }

    /**
     * Categories Management
     */
    public function categories(): void
    {
        $categories = $this->db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1) as products_count 
             FROM categories c 
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        require_once __DIR__ . '/../../views/products/categories.php';
    }

    public function storeCategory(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('categories');
        }

        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#4f46e5';
        $icon = trim($_POST['icon'] ?? 'utensils');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (empty($name)) {
            set_flash('error', 'يرجى كتابة اسم التصنيف');
            redirect('categories');
        }

        $slug = 'cat-' . time() . '-' . rand(10, 99);

        $this->db->insert('categories', [
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'color' => $color,
            'sort_order' => $sortOrder,
            'is_active' => 1
        ]);

        log_audit('إضافة تصنيف', 'categories', "إضافة تصنيف جديد {$name}");
        set_flash('success', 'تمت إضافة التصنيف بنجاح');
        redirect('categories');
    }
}
