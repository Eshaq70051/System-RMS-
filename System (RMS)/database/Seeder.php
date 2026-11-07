<?php

namespace Database;

use App\Models\Database;

class Seeder
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        // 1. Branches
        $branchCount = $this->db->fetchValue("SELECT COUNT(*) FROM branches");
        if ($branchCount == 0) {
            $this->db->insert('branches', [
                'name' => 'الفرع الرئيسي - الرياض (العليا)',
                'code' => 'BR-01',
                'phone' => '0112345678',
                'email' => 'riyadh@restaurant.com',
                'address' => 'طريق الملك فهد، العليا، الرياض',
                'tax_number' => '310987654300003',
                'commercial_registration' => '1010987654',
                'is_active' => 1
            ]);

            $this->db->insert('branches', [
                'name' => 'فرع جدة (الكورنيش)',
                'code' => 'BR-02',
                'phone' => '0129876543',
                'email' => 'jeddah@restaurant.com',
                'address' => 'شارع الكورنيش الشمالي، جدة',
                'tax_number' => '310987654300003',
                'commercial_registration' => '1010987654',
                'is_active' => 1
            ]);
        }

        // 2. Roles
        $roleCount = $this->db->fetchValue("SELECT COUNT(*) FROM roles");
        if ($roleCount == 0) {
            $roles = [
                ['name' => 'المدير العام', 'slug' => 'admin', 'description' => 'صلاحية كاملة لجميع أقسام ووظائف النظام'],
                ['name' => 'مدير الفرع', 'slug' => 'branch_manager', 'description' => 'إدارة عمليات الفرع والمخزون والمبيعات'],
                ['name' => 'الكاشير', 'slug' => 'cashier', 'description' => 'شاشة نقاط البيع، الفواتير، الورديات، المرتجعات'],
                ['name' => 'شاشة المطبخ (KDS)', 'slug' => 'kitchen', 'description' => 'استعراض الطلبات وتحديث حالات التجهيز في المطبخ'],
                ['name' => 'المحاسب المالي', 'slug' => 'accountant', 'description' => 'إدارة الحسابات، القيود، المصروفات، والتقارير المالية'],
                ['name' => 'مسؤول المستودع والمشتريات', 'slug' => 'inventory_manager', 'description' => 'إدارة المواد الخام، فواتير الشراء، والجرد']
            ];
            foreach ($roles as $r) {
                $this->db->insert('roles', $r);
            }
        }

        // 3. Permissions
        $permCount = $this->db->fetchValue("SELECT COUNT(*) FROM permissions");
        if ($permCount == 0) {
            $perms = [
                ['name' => 'عرض لوحة التحكم', 'slug' => 'dashboard_view', 'module' => 'dashboard'],
                ['name' => 'استخدام نقاط البيع POS', 'slug' => 'pos_access', 'module' => 'pos'],
                ['name' => 'تطبيق الخصم في الكاشير', 'slug' => 'pos_discount', 'module' => 'pos'],
                ['name' => 'إلغاء ومرتجع الطلبات', 'slug' => 'pos_refund', 'module' => 'pos'],
                ['name' => 'شاشة المطبخ KDS', 'slug' => 'kds_access', 'module' => 'kds'],
                ['name' => 'إدارة الطاولات', 'slug' => 'tables_manage', 'module' => 'tables'],
                ['name' => 'إدارة المنتجات والقوائم', 'slug' => 'products_manage', 'module' => 'products'],
                ['name' => 'إدارة المخزون والمواد الخام', 'slug' => 'inventory_manage', 'module' => 'inventory'],
                ['name' => 'إدارة الوصفات وتكلفة الوجبات', 'slug' => 'recipes_manage', 'module' => 'recipes'],
                ['name' => 'إدارة المشتريات والموردين', 'slug' => 'purchases_manage', 'module' => 'purchases'],
                ['name' => 'إدارة الموظفين والرواتب', 'slug' => 'employees_manage', 'module' => 'employees'],
                ['name' => 'إدارة المالية والمصروفات', 'slug' => 'financial_manage', 'module' => 'financial'],
                ['name' => 'عرض التقارير الشاملة', 'slug' => 'reports_view', 'module' => 'reports'],
                ['name' => 'تعديل الإعدادات العامة', 'slug' => 'settings_manage', 'module' => 'settings'],
            ];
            foreach ($perms as $p) {
                $this->db->insert('permissions', $p);
            }

            // Assign all permissions to Admin role
            $allPerms = $this->db->fetchAll("SELECT id FROM permissions");
            $adminRole = $this->db->fetch("SELECT id FROM roles WHERE slug = 'admin'");
            if ($adminRole) {
                foreach ($allPerms as $p) {
                    $this->db->insert('role_permissions', [
                        'role_id' => $adminRole['id'],
                        'permission_id' => $p['id']
                    ]);
                }
            }
        }

        // 4. Users
        $userCount = $this->db->fetchValue("SELECT COUNT(*) FROM users");
        if ($userCount == 0) {
            $adminRole = $this->db->fetch("SELECT id FROM roles WHERE slug = 'admin'");
            $cashierRole = $this->db->fetch("SELECT id FROM roles WHERE slug = 'cashier'");
            $kitchenRole = $this->db->fetch("SELECT id FROM roles WHERE slug = 'kitchen'");
            $accRole = $this->db->fetch("SELECT id FROM roles WHERE slug = 'accountant'");

            $users = [
                [
                    'branch_id' => 1,
                    'role_id' => $adminRole['id'],
                    'name' => 'عبدالله السعيد (المدير العام)',
                    'username' => 'admin',
                    'email' => 'admin@restaurant.com',
                    'password' => password_hash('admin123', PASSWORD_BCRYPT),
                    'phone' => '0501112233',
                    'is_active' => 1
                ],
                [
                    'branch_id' => 1,
                    'role_id' => $cashierRole['id'],
                    'name' => 'محمد الكاشير',
                    'username' => 'cashier',
                    'email' => 'cashier@restaurant.com',
                    'password' => password_hash('cashier123', PASSWORD_BCRYPT),
                    'phone' => '0502223344',
                    'is_active' => 1
                ],
                [
                    'branch_id' => 1,
                    'role_id' => $kitchenRole['id'],
                    'name' => 'الشيف علاء (المطبخ)',
                    'username' => 'kitchen',
                    'email' => 'kitchen@restaurant.com',
                    'password' => password_hash('kitchen123', PASSWORD_BCRYPT),
                    'phone' => '0503334455',
                    'is_active' => 1
                ],
                [
                    'branch_id' => 1,
                    'role_id' => $accRole['id'],
                    'name' => 'فهد المحاسب',
                    'username' => 'accountant',
                    'email' => 'accountant@restaurant.com',
                    'password' => password_hash('acc123', PASSWORD_BCRYPT),
                    'phone' => '0504445566',
                    'is_active' => 1
                ]
            ];
            foreach ($users as $u) {
                $this->db->insert('users', $u);
            }
        }

        // 5. Dining Tables
        $tableCount = $this->db->fetchValue("SELECT COUNT(*) FROM dining_tables");
        if ($tableCount == 0) {
            $sections = [
                ['name' => 'الصالة الرئيسية', 'prefix' => 'T', 'count' => 6, 'capacity' => 4],
                ['name' => 'قسم العوائل', 'prefix' => 'F', 'count' => 4, 'capacity' => 6],
                ['name' => 'التراس الخارجي VIP', 'prefix' => 'VIP', 'count' => 3, 'capacity' => 8],
            ];
            foreach ($sections as $sec) {
                for ($i = 1; $i <= $sec['count']; $i++) {
                    $this->db->insert('dining_tables', [
                        'branch_id' => 1,
                        'table_number' => $sec['prefix'] . '-' . $i,
                        'section' => $sec['name'],
                        'capacity' => $sec['capacity'],
                        'status' => 'available'
                    ]);
                }
            }
        }

        // 6. Categories
        $catCount = $this->db->fetchValue("SELECT COUNT(*) FROM categories");
        if ($catCount == 0) {
            $categories = [
                ['name' => 'البرجر والساندوتشات', 'slug' => 'burgers', 'icon' => 'hamburger', 'color' => '#ef4444', 'sort_order' => 1],
                ['name' => 'المشويات واللحوم', 'slug' => 'grills', 'icon' => 'fire', 'color' => '#f97316', 'sort_order' => 2],
                ['name' => 'البيتزا والباستا', 'slug' => 'pizza-pasta', 'icon' => 'pizza-slice', 'color' => '#eab308', 'sort_order' => 3],
                ['name' => 'المقبلات والشوربات', 'slug' => 'appetizers', 'icon' => 'bowl-food', 'color' => '#10b981', 'sort_order' => 4],
                ['name' => 'المشروبات والعصائر', 'slug' => 'beverages', 'icon' => 'glass-water', 'color' => '#06b6d4', 'sort_order' => 5],
                ['name' => 'الحلويات والمثلجات', 'slug' => 'desserts', 'icon' => 'ice-cream', 'color' => '#8b5cf6', 'sort_order' => 6],
            ];
            foreach ($categories as $c) {
                $this->db->insert('categories', $c);
            }
        }

        // 7. Inventory Items (Raw materials)
        $invCount = $this->db->fetchValue("SELECT COUNT(*) FROM inventory_items");
        if ($invCount == 0) {
            $rawMaterials = [
                ['name' => 'لحم بقري أنجوس طازج', 'sku' => 'RAW-BEEF-01', 'unit' => 'كجم', 'min_stock_alert' => 15, 'current_stock' => 85.5, 'cost_per_unit' => 38.00],
                ['name' => 'صدور دجاج متبلة طازجة', 'sku' => 'RAW-CHICK-01', 'unit' => 'كجم', 'min_stock_alert' => 20, 'current_stock' => 120.0, 'cost_per_unit' => 22.00],
                ['name' => 'خبز برجر بريوش ذهبي', 'sku' => 'RAW-BUN-01', 'unit' => 'حبة', 'min_stock_alert' => 50, 'current_stock' => 340.0, 'cost_per_unit' => 1.50],
                ['name' => 'جبن شيدر أصفر شرائح', 'sku' => 'RAW-CHED-01', 'unit' => 'حبة', 'min_stock_alert' => 40, 'current_stock' => 280.0, 'cost_per_unit' => 0.80],
                ['name' => 'بطاطس ودجز مجمدة', 'sku' => 'RAW-POT-01', 'unit' => 'كجم', 'min_stock_alert' => 25, 'current_stock' => 90.0, 'cost_per_unit' => 11.50],
                ['name' => 'زيت قلي نباتي نقي', 'sku' => 'RAW-OIL-01', 'unit' => 'لتر', 'min_stock_alert' => 20, 'current_stock' => 75.0, 'cost_per_unit' => 7.50],
                ['name' => 'خضار مشكلة طازجة (طماطم/خس/بصل)', 'sku' => 'RAW-VEG-01', 'unit' => 'كجم', 'min_stock_alert' => 15, 'current_stock' => 45.0, 'cost_per_unit' => 4.50],
                ['name' => 'عجينة بيتزا جاهزة فاخرة', 'sku' => 'RAW-DOUGH-01', 'unit' => 'قطعة', 'min_stock_alert' => 20, 'current_stock' => 60.0, 'cost_per_unit' => 4.00],
                ['name' => 'جبن موزاريلا إيطالية مبشورة', 'sku' => 'RAW-MOZZ-01', 'unit' => 'كجم', 'min_stock_alert' => 15, 'current_stock' => 38.0, 'cost_per_unit' => 28.00],
                ['name' => 'أرز بسمتي عنبر فاخر', 'sku' => 'RAW-RICE-01', 'unit' => 'كجم', 'min_stock_alert' => 30, 'current_stock' => 150.0, 'cost_per_unit' => 7.00],
                ['name' => 'صلصات وبهارات سرية', 'sku' => 'RAW-SAUCE-01', 'unit' => 'كجم', 'min_stock_alert' => 10, 'current_stock' => 32.0, 'cost_per_unit' => 24.00],
                ['name' => 'علب ومغلفات تيك أواي فاخرة', 'sku' => 'RAW-PKG-01', 'unit' => 'حبة', 'min_stock_alert' => 100, 'current_stock' => 650.0, 'cost_per_unit' => 0.65],
                ['name' => 'علب كولا غازية 330 مل', 'sku' => 'RAW-COLA-01', 'unit' => 'حبة', 'min_stock_alert' => 48, 'current_stock' => 180.0, 'cost_per_unit' => 2.10],
            ];
            foreach ($rawMaterials as $mat) {
                $mat['branch_id'] = 1;
                $this->db->insert('inventory_items', $mat);
            }
        }

        // 8. Products & Recipes
        $prodCount = $this->db->fetchValue("SELECT COUNT(*) FROM products");
        if ($prodCount == 0) {
            $catBurgers = $this->db->fetch("SELECT id FROM categories WHERE slug = 'burgers'")['id'];
            $catGrills = $this->db->fetch("SELECT id FROM categories WHERE slug = 'grills'")['id'];
            $catPizza = $this->db->fetch("SELECT id FROM categories WHERE slug = 'pizza-pasta'")['id'];
            $catApp = $this->db->fetch("SELECT id FROM categories WHERE slug = 'appetizers'")['id'];
            $catBev = $this->db->fetch("SELECT id FROM categories WHERE slug = 'beverages'")['id'];
            $catDes = $this->db->fetch("SELECT id FROM categories WHERE slug = 'desserts'")['id'];

            $products = [
                [
                    'category_id' => $catBurgers,
                    'name' => 'برجر لحم بلاك أنجوس ديلوكس',
                    'barcode' => '628100101',
                    'sku' => 'BRG-BEEF-01',
                    'description' => 'شريحة لحم أنجوس مشوية 180جم مع جبنة شيدر وخس وطماطم وصوص خاص في خبز بريوش',
                    'price' => 38.00,
                    'cost_price' => 12.80,
                    'unit' => 'وجبة',
                    'has_recipe' => 1,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catBurgers,
                    'name' => 'برجر دجاج كرسبي مقرمش',
                    'barcode' => '628100102',
                    'sku' => 'BRG-CHICK-01',
                    'description' => 'صدر دجاج مقرمش ذهبي مع صوص المايونيز الحار والخس المقرمش',
                    'price' => 32.00,
                    'cost_price' => 9.50,
                    'unit' => 'وجبة',
                    'has_recipe' => 1,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catGrills,
                    'name' => 'كباب لحم نعيمي بلدي مشوي',
                    'barcode' => '628100201',
                    'sku' => 'GRL-KABAB-01',
                    'description' => '3 أسياخ كباب لحم طازج مشوي على الفحم يقدم مع الأرز والخبز الساخن والسلطة',
                    'price' => 54.00,
                    'cost_price' => 18.20,
                    'unit' => 'طبق',
                    'has_recipe' => 1,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catGrills,
                    'name' => 'شيش طاووق على الفحم',
                    'barcode' => '628100202',
                    'sku' => 'GRL-TAWOOK-01',
                    'description' => 'قطع دجاج متبلة بالثوم والليمون ومشوية مع الثومية والبطاطس',
                    'price' => 46.00,
                    'cost_price' => 13.50,
                    'unit' => 'طبق',
                    'has_recipe' => 1,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catPizza,
                    'name' => 'بيتزا بيبروني كلاسيك',
                    'barcode' => '628100301',
                    'sku' => 'PIZ-PEP-01',
                    'description' => 'عجينة رقيقة مع صلصة طماطم إيطالية وموزاريلا فاخرة وشرائح بيبروني بقري',
                    'price' => 44.00,
                    'cost_price' => 12.00,
                    'unit' => 'قطعة',
                    'has_recipe' => 1,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catApp,
                    'name' => 'بطاطس متبلة ودجز',
                    'barcode' => '628100401',
                    'sku' => 'APP-POT-01',
                    'description' => 'أصابع بطاطس مقلية ذهبية مقرمشة بالأعشاب الإيطالية',
                    'price' => 16.00,
                    'cost_price' => 3.80,
                    'unit' => 'صحن',
                    'has_recipe' => 1,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catBev,
                    'name' => 'عصير برتقال طازج طبيعي',
                    'barcode' => '628100501',
                    'sku' => 'BEV-ORNG-01',
                    'description' => 'عصير برتقال طبيعي 100% معصور طازج بدون سكر مضاف',
                    'price' => 18.00,
                    'cost_price' => 4.50,
                    'unit' => 'كوب',
                    'has_recipe' => 0,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catBev,
                    'name' => 'كوكاكولا كانز 330 مل',
                    'barcode' => '628100502',
                    'sku' => 'BEV-COLA-01',
                    'description' => 'مشروب غازي بارد منعش',
                    'price' => 6.00,
                    'cost_price' => 2.10,
                    'unit' => 'علبة',
                    'has_recipe' => 0,
                    'is_available' => 1,
                    'is_active' => 1
                ],
                [
                    'category_id' => $catDes,
                    'name' => 'كعكة شوكولاتة فادج مع آيسكريم',
                    'barcode' => '628100601',
                    'sku' => 'DES-FUDGE-01',
                    'description' => 'كعكة دافئة غنية بالشوكولاتة البلجيكية مع بولة فانيليا',
                    'price' => 24.00,
                    'cost_price' => 7.00,
                    'unit' => 'صحن',
                    'has_recipe' => 0,
                    'is_available' => 1,
                    'is_active' => 1
                ]
            ];

            foreach ($products as $p) {
                $pId = $this->db->insert('products', $p);

                // Add variants and addons for burgers
                if (str_contains($p['name'], 'برجر')) {
                    $this->db->insert('product_variants', ['product_id' => $pId, 'name' => 'حجم عادي (Single)', 'additional_price' => 0.00]);
                    $this->db->insert('product_variants', ['product_id' => $pId, 'name' => 'حجم مضاعف (Double)', 'additional_price' => 14.00]);

                    $this->db->insert('product_addons', ['product_id' => $pId, 'name' => 'إضافة جبنة شيدر إكسترا', 'price' => 4.00]);
                    $this->db->insert('product_addons', ['product_id' => $pId, 'name' => 'إضافة صوص الترافل الخاص', 'price' => 5.00]);
                    $this->db->insert('product_addons', ['product_id' => $pId, 'name' => 'إضافة هالبينو حار', 'price' => 3.00]);
                }
            }

            // Create Recipes linking products to raw materials
            $beefItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-BEEF-01'");
            $bunItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-BUN-01'");
            $chedItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-CHED-01'");
            $vegItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-VEG-01'");
            $chickItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-CHICK-01'");
            $riceItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-RICE-01'");
            $potItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-POT-01'");
            $oilItem = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = 'RAW-OIL-01'");

            $beefBurger = $this->db->fetch("SELECT id FROM products WHERE sku = 'BRG-BEEF-01'");
            if ($beefBurger && $beefItem && $bunItem && $chedItem) {
                $rId = $this->db->insert('recipes', [
                    'product_id' => $beefBurger['id'],
                    'name' => 'وصفة برجر أنجوس ديلوكس القياسية',
                    'prep_time_minutes' => 12,
                    'instructions' => 'شوي شريحة اللحم 180جم على حرارة 200 درجة، تحميص الخبز، إضافة الصوص والجبن والخضار',
                    'calculated_cost' => 12.80
                ]);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $beefItem['id'], 'quantity_required' => 0.180, 'unit' => 'كجم']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $bunItem['id'], 'quantity_required' => 1.000, 'unit' => 'حبة']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $chedItem['id'], 'quantity_required' => 1.000, 'unit' => 'حبة']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $vegItem['id'], 'quantity_required' => 0.050, 'unit' => 'كجم']);
            }

            $chickBurger = $this->db->fetch("SELECT id FROM products WHERE sku = 'BRG-CHICK-01'");
            if ($chickBurger && $chickItem && $bunItem) {
                $rId = $this->db->insert('recipes', [
                    'product_id' => $chickBurger['id'],
                    'name' => 'وصفة برجر دجاج كرسبي',
                    'prep_time_minutes' => 10,
                    'instructions' => 'قلي صدر الدجاج بالزيت المغلي حتى القرمشة، وضع صوص المايونيز والخس',
                    'calculated_cost' => 9.50
                ]);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $chickItem['id'], 'quantity_required' => 0.160, 'unit' => 'كجم']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $bunItem['id'], 'quantity_required' => 1.000, 'unit' => 'حبة']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $oilItem['id'], 'quantity_required' => 0.040, 'unit' => 'لتر']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $vegItem['id'], 'quantity_required' => 0.040, 'unit' => 'كجم']);
            }

            $kababProd = $this->db->fetch("SELECT id FROM products WHERE sku = 'GRL-KABAB-01'");
            if ($kababProd && $beefItem && $riceItem) {
                $rId = $this->db->insert('recipes', [
                    'product_id' => $kababProd['id'],
                    'name' => 'وصفة كباب اللحم المشوي مع الأرز',
                    'prep_time_minutes' => 18,
                    'instructions' => 'شوي الكباب على فحم السمر، تزيين طبق الأرز بالبصل المشوي والبقدونس',
                    'calculated_cost' => 18.20
                ]);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $beefItem['id'], 'quantity_required' => 0.250, 'unit' => 'كجم']);
                $this->db->insert('recipe_ingredients', ['recipe_id' => $rId, 'inventory_item_id' => $riceItem['id'], 'quantity_required' => 0.200, 'unit' => 'كجم']);
            }
        }

        // 9. Suppliers
        $supCount = $this->db->fetchValue("SELECT COUNT(*) FROM suppliers");
        if ($supCount == 0) {
            $suppliers = [
                ['name' => 'سالم الشهري', 'company_name' => 'شركة المراعي الوطنية للتوزيع', 'phone' => '0551122334', 'email' => 'sales@almarai-supply.com', 'address' => 'المنطقة الصناعية، الرياض', 'tax_number' => '300098765400003', 'current_balance' => 3200.00],
                ['name' => 'فهد القحطاني', 'company_name' => 'مؤسسة الشرق للحوم والمواشي', 'phone' => '0559988776', 'email' => 'meat@alsharq.com', 'address' => 'سوق الماشية المركزي، الرياض', 'tax_number' => '300087654300003', 'current_balance' => 5400.00],
                ['name' => 'خالد المهيدب', 'company_name' => 'شركة الأفق لمستلزمات المطاعم والتغليف', 'phone' => '0553344556', 'email' => 'pack@alofouq.com', 'address' => 'حي السلي، الرياض', 'tax_number' => '300076543200003', 'current_balance' => 1250.00],
            ];
            foreach ($suppliers as $s) {
                $this->db->insert('suppliers', $s);
            }
        }

        // 10. Expense Categories & Sample Expenses
        $expCatCount = $this->db->fetchValue("SELECT COUNT(*) FROM expense_categories");
        if ($expCatCount == 0) {
            $cats = [
                ['name' => 'إيجار الموقع', 'description' => 'إيجار مبنى الفرع السنوي والشهري'],
                ['name' => 'فواتير الطاقة والمياه', 'description' => 'سداد فواتير شركة الكهرباء والمياه الوطنية'],
                ['name' => 'صيانة وتشغيل', 'description' => 'صيانة معدات المطبخ، التكييف، وبرادات التجميد'],
                ['name' => 'دعاية وتسويق', 'description' => 'حملات تيك توك وجوجل وتصوير الوجبات'],
                ['name' => 'نظافة ومستهلكات تشغيلية', 'description' => 'مواد تعقيم، صابون، أدوات تنظيف'],
                ['name' => 'ضيافة وبوفيه موظفين', 'description' => 'مشروبات وضيافة فريق العمل']
            ];
            foreach ($cats as $ec) {
                $this->db->insert('expense_categories', $ec);
            }

            // Sample expenses
            $catId1 = $this->db->fetch("SELECT id FROM expense_categories WHERE name = 'فواتير الطاقة والمياه'")['id'];
            $catId2 = $this->db->fetch("SELECT id FROM expense_categories WHERE name = 'نظافة ومستهلكات تشغيلية'")['id'];

            $this->db->insert('expenses', [
                'branch_id' => 1,
                'expense_category_id' => $catId1,
                'title' => 'سداد فاتورة كهرباء الفرع الرئيسي لشهر أكتوبر',
                'amount' => 1450.00,
                'date' => date('Y-m-d', strtotime('-5 days')),
                'payment_method' => 'bank',
                'paid_to' => 'الشركة السعودية للكهرباء',
                'notes' => 'تم الدفع عبر سداد البنكي',
                'created_by' => 1
            ]);

            $this->db->insert('expenses', [
                'branch_id' => 1,
                'expense_category_id' => $catId2,
                'title' => 'شراء مواد تنظيف ومعقمات أرضيات المطبخ',
                'amount' => 380.00,
                'date' => date('Y-m-d', strtotime('-2 days')),
                'payment_method' => 'cash',
                'paid_to' => 'متجر المنظفات الحديثة',
                'notes' => 'دفع نقدي من صندوق الصالة',
                'created_by' => 1
            ]);
        }

        // 11. Employees
        $empCount = $this->db->fetchValue("SELECT COUNT(*) FROM employees");
        if ($empCount == 0) {
            $employees = [
                ['full_name' => 'علاء الدين حسن', 'phone' => '0503334455', 'job_title' => 'شيف تنفيذي (رئيس المطبخ)', 'department' => 'المطبخ والإنتاج', 'salary' => 7500.00, 'hire_date' => '2023-01-15'],
                ['full_name' => 'محمد الكاشير', 'phone' => '0502223344', 'job_title' => 'أخصائي مبيعات وكاشير', 'department' => 'الخدمة ونقاط البيع', 'salary' => 4500.00, 'hire_date' => '2023-06-01'],
                ['full_name' => 'سامر الزهراني', 'phone' => '0507778899', 'job_title' => 'طاهي مشويات وشواء', 'department' => 'المطبخ والإنتاج', 'salary' => 5200.00, 'hire_date' => '2023-08-10'],
                ['full_name' => 'فهد المحاسب', 'phone' => '0504445566', 'job_title' => 'محاسب مالي وتكاليف', 'department' => 'الإدارة المالية', 'salary' => 6000.00, 'hire_date' => '2023-03-20'],
                ['full_name' => 'طارق كمال', 'phone' => '0509990011', 'job_title' => 'مندوب توصيل طلبات', 'department' => 'التوصيل واللوجستيات', 'salary' => 3500.00, 'hire_date' => '2024-01-10'],
            ];
            foreach ($employees as $emp) {
                $emp['branch_id'] = 1;
                $this->db->insert('employees', $emp);
            }
        }

        // 12. Settings
        $settingCount = $this->db->fetchValue("SELECT COUNT(*) FROM settings");
        if ($settingCount == 0) {
            $settings = [
                ['setting_key' => 'restaurant_name', 'setting_value' => 'مطعم القصر الفاخر'],
                ['setting_key' => 'restaurant_phone', 'setting_value' => '0112345678'],
                ['setting_key' => 'restaurant_email', 'setting_value' => 'info@restaurant-palace.com'],
                ['setting_key' => 'restaurant_address', 'setting_value' => 'شارع التحلية، حي السليمانية، الرياض'],
                ['setting_key' => 'tax_number', 'setting_value' => '310987654300003'],
                ['setting_key' => 'commercial_registration', 'setting_value' => '1010987654'],
                ['setting_key' => 'currency', 'setting_value' => 'YER'],
                ['setting_key' => 'currency_symbol', 'setting_value' => 'ر.ي'],
                ['setting_key' => 'tax_rate', 'setting_value' => '15'],
                ['setting_key' => 'tax_enabled', 'setting_value' => '1'],
                ['setting_key' => 'order_prefix', 'setting_value' => 'ORD-'],
                ['setting_key' => 'invoice_footer', 'setting_value' => 'شكراً لاختياركم مطعمنا! أسعدنا خدمتكم وننتظر زيارتكم القادمة.'],
                ['setting_key' => 'auto_kitchen_print', 'setting_value' => '1'],
                ['setting_key' => 'enable_recipe_deduction', 'setting_value' => '1']
            ];
            foreach ($settings as $s) {
                $this->db->insert('settings', $s);
            }
        }

        // 13. Create a Cashier Shift & Completed Orders to furnish initial dashboard and reports!
        $shiftCount = $this->db->fetchValue("SELECT COUNT(*) FROM cashier_shifts");
        if ($shiftCount == 0) {
            $adminUser = $this->db->fetch("SELECT id FROM users WHERE username = 'admin'");
            $uId = $adminUser ? $adminUser['id'] : 1;

            $shiftId = $this->db->insert('cashier_shifts', [
                'branch_id' => 1,
                'user_id' => $uId,
                'opened_at' => date('Y-m-d 09:00:00'),
                'opening_cash' => 500.00,
                'total_sales' => 0.00,
                'total_returns' => 0.00,
                'total_cash_sales' => 0.00,
                'total_card_sales' => 0.00,
                'status' => 'open'
            ]);

            // Add opening balance to cash drawer transactions
            $this->db->insert('cash_drawer_transactions', [
                'branch_id' => 1,
                'shift_id' => $shiftId,
                'type' => 'in',
                'amount' => 500.00,
                'category' => 'opening_balance',
                'description' => 'رصيد افتتاح الصندوق للوردية الصباحية',
                'created_by' => $uId
            ]);
        }
    }
}
