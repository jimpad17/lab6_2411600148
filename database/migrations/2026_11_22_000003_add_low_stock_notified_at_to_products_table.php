<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remembers when the last low-stock alert was sent so repeated stock-outs
     * on an already-critical product do not flood the admin inbox.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'low_stock_notified_at')) {
                $table->timestamp('low_stock_notified_at')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'low_stock_notified_at')) {
                $table->dropColumn('low_stock_notified_at');
            }
        });
    }
};
