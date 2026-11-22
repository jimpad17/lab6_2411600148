<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'stockout_notified_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->timestamp('stockout_notified_at')->nullable()->after('low_stock_notified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'stockout_notified_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stockout_notified_at');
            });
        }
    }
};
