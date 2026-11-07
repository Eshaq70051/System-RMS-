<?php

namespace App\Controllers;

use App\Models\Database;

class RecipeController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $recipes = $this->db->fetchAll(
            "SELECT r.*, p.name as product_name, p.price as selling_price, p.image as product_image, c.name as category_name,
                    (SELECT COUNT(*) FROM recipe_ingredients WHERE recipe_id = r.id) as ingredients_count 
             FROM recipes r 
             JOIN products p ON r.product_id = p.id 
             JOIN categories c ON p.category_id = c.id 
             ORDER BY r.id DESC"
        );

        // Fetch recipes with ingredients
        foreach ($recipes as &$r) {
            $r['ingredients'] = $this->db->fetchAll(
                "SELECT ri.*, ii.name as item_name, ii.cost_per_unit, (ri.quantity_required * ii.cost_per_unit) as ingredient_cost 
                 FROM recipe_ingredients ri 
                 JOIN inventory_items ii ON ri.inventory_item_id = ii.id 
                 WHERE ri.recipe_id = :rid",
                ['rid' => $r['id']]
            );

            // Re-calculate actual current cost based on latest inventory unit costs
            $freshCost = 0.0;
            foreach ($r['ingredients'] as $ing) {
                $freshCost += $ing['ingredient_cost'];
            }
            $r['current_calculated_cost'] = round($freshCost, 2);
            $r['profit_margin'] = $r['selling_price'] > 0 ? round((($r['selling_price'] - $freshCost) / $r['selling_price']) * 100, 1) : 0;
        }

        // Products without recipe yet
        $availableProducts = $this->db->fetchAll(
            "SELECT id, name, price FROM products WHERE id NOT IN (SELECT product_id FROM recipes) AND is_active = 1 ORDER BY name ASC"
        );

        $rawMaterials = $this->db->fetchAll("SELECT id, name, unit, cost_per_unit FROM inventory_items ORDER BY name ASC");

        require_once __DIR__ . '/../../views/recipes/index.php';
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('recipes');
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $prepTime = (int)($_POST['prep_time_minutes'] ?? 15);
        $instructions = trim($_POST['instructions'] ?? '');
        $ingredients = $_POST['ingredients'] ?? []; // Array of [item_id, quantity, waste]

        if (!$productId || empty($name) || empty($ingredients)) {
            set_flash('error', 'يرجى اختيار الوجبة وإضافة المكونات والكميات');
            redirect('recipes');
        }

        $this->db->beginTransaction();
        try {
            $totalCost = 0.0;

            $recipeId = $this->db->insert('recipes', [
                'product_id' => $productId,
                'name' => $name,
                'prep_time_minutes' => $prepTime,
                'instructions' => $instructions,
                'calculated_cost' => 0.00
            ]);

            foreach ($ingredients as $ing) {
                $itemId = (int)($ing['item_id'] ?? 0);
                $qty = (float)($ing['quantity'] ?? 0);
                $waste = (float)($ing['waste'] ?? 0);

                if (!$itemId || $qty <= 0) continue;

                $rawItem = $this->db->fetch("SELECT cost_per_unit, unit FROM inventory_items WHERE id = :id", ['id' => $itemId]);
                if ($rawItem) {
                    $itemCost = $qty * (float)$rawItem['cost_per_unit'];
                    $totalCost += $itemCost;

                    $this->db->insert('recipe_ingredients', [
                        'recipe_id' => $recipeId,
                        'inventory_item_id' => $itemId,
                        'quantity_required' => $qty,
                        'unit' => $rawItem['unit'],
                        'waste_percentage' => $waste
                    ]);
                }
            }

            // Update recipe and product cost_price
            $this->db->update('recipes', ['calculated_cost' => $totalCost], 'id = :id', ['id' => $recipeId]);
            $this->db->update('products', ['cost_price' => $totalCost, 'has_recipe' => 1], 'id = :pid', ['pid' => $productId]);

            log_audit('إنشاء وصفة', 'recipes', "إنشاء وصفة للوجبة #{$productId} بتكلفة تقديرية {$totalCost} ر.س");
            $this->db->commit();
            set_flash('success', "تم إنشاء الوصفة بنجاح وحساب تكلفة الوجبة تلقائياً ({$totalCost} ر.س)");
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'خطأ أثناء إنشاء الوصفة: ' . $e->getMessage());
        }

        redirect('recipes');
    }

    public function delete(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('recipes');
        }

        $recipe = $this->db->fetch("SELECT product_id FROM recipes WHERE id = :id", ['id' => $id]);
        if ($recipe) {
            $this->db->update('products', ['has_recipe' => 0], 'id = :pid', ['pid' => $recipe['product_id']]);
            $this->db->delete('recipes', 'id = :id', ['id' => $id]);
            log_audit('حذف وصفة', 'recipes', "حذف الوصفة رقم #{$id}");
            set_flash('success', 'تم حذف الوصفة بنجاح');
        }

        redirect('recipes');
    }
}
