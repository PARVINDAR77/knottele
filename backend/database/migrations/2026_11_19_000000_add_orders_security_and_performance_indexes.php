<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the security and performance index migrations.
     * Completely non-destructive and idempotent.
     */
    public function up(): void
    {
        // 1. Performance index for Orders lookup by customer & date
        if (Schema::hasTable('orders')) {
            $this->addIndexSafely('orders', ['customer_id', 'created_at'], 'idx_orders_cust_created');
            $this->addIndexSafely('orders', ['status', 'created_at'], 'idx_orders_status_created');
            $this->addIndexSafely('orders', ['payment_status'], 'idx_orders_payment_status');
        }

        // 2. High-performance index for Custom Order lookups
        if (Schema::hasTable('custom_order_requests')) {
            $this->addIndexSafely('custom_order_requests', ['reference_id'], 'idx_custom_orders_ref');
            $this->addIndexSafely('custom_order_requests', ['customer_email'], 'idx_custom_orders_email');
        }

        // 3. Performance index for Visitor tracking
        if (Schema::hasTable('visitors')) {
            $this->addIndexSafely('visitors', ['ip_address', 'visit_date'], 'idx_visitors_ip_date');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexSafely('orders', 'idx_orders_cust_created');
        $this->dropIndexSafely('orders', 'idx_orders_status_created');
        $this->dropIndexSafely('orders', 'idx_orders_payment_status');
        $this->dropIndexSafely('custom_order_requests', 'idx_custom_orders_ref');
        $this->dropIndexSafely('custom_order_requests', 'idx_custom_orders_email');
        $this->dropIndexSafely('visitors', 'idx_visitors_ip_date');
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
