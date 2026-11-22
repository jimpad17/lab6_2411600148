<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'receipt_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('receipt_number', 40)->nullable()->unique()->after('order_number');
            });
        }

        // Give already-approved orders a receipt number so the Receipt page
        // is useful immediately after the migration.
        DB::table('orders')
            ->where('status', 'approved')
            ->whereNull('receipt_number')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'receipt_number' => sprintf('RCP-%s-%04d', date('Y'), $order->id),
                ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'receipt_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('receipt_number');
            });
        }
    }
};
