<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->string('type', 20);                 // purchase | sales
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('original_quantity')->nullable();
            $table->date('order_date');
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->string('reference_number', 150)->nullable();
            $table->text('notes')->nullable();
            $table->text('edit_notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('order_date');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
