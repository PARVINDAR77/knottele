<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the performance index migrations.
     * Completely non-destructive and idempotent.
     */
    public function up(): void
    {
        // 1. Performance index for Media queries
        if (Schema::hasTable('media')) {
            $this->addIndexSafely('media', ['page', 'section', 'is_active', 'sort_order'], 'idx_media_page_section_active_sort');
        }

        // 2. Performance index for Category listings
        if (Schema::hasTable('categories')) {
            $this->addIndexSafely('categories', ['status', 'parent_id', 'sort_order'], 'idx_categories_status_parent_sort');
        }

        // 3. Performance index for Active Offers
        if (Schema::hasTable('offers')) {
            $this->addIndexSafely('offers', ['status', 'discount_value'], 'idx_offers_status_discount');
        }

        // 4. Performance index for Customer Addresses lookup
        if (Schema::hasTable('customer_addresses')) {
            $this->addIndexSafely('customer_addresses', ['customer_id', 'is_default'], 'idx_cust_addr_cust_default');
        }

        // 5. Performance index for Products storefront filters
        if (Schema::hasTable('products')) {
            $this->addIndexSafely('products', ['status', 'main_category_id', 'is_featured'], 'idx_products_status_cat_featured');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexSafely('media', 'idx_media_page_section_active_sort');
        $this->dropIndexSafely('categories', 'idx_categories_status_parent_sort');
        $this->dropIndexSafely('offers', 'idx_offers_status_discount');
        $this->dropIndexSafely('customer_addresses', 'idx_cust_addr_cust_default');
        $this->dropIndexSafely('products', 'idx_products_status_cat_featured');
    }

    protected function addIndexSafely(string $tableName, array $columns, string $indexName): void
    {
        try {
            $existingIndexes = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
            if (empty($existingIndexes)) {
                Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
            }
        } catch (\Throwable $e) {
            \Log::info("Note: Could not add index {$indexName} to {$tableName}: " . $e->getMessage());
        }
    }

    protected function dropIndexSafely(string $tableName, string $indexName): void
    {
        try {
            if (Schema::hasTable($tableName)) {
                $existingIndexes = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
                if (!empty($existingIndexes)) {
                    Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
