<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab 6 extends the Lab 5 stock-movement table WITHOUT dropping any rows.
     * The old `reference` column is kept readable through a model accessor.
     */
    public function up(): void
    {
        // Rename separately: Schema::hasColumn() is evaluated while the
        // blueprint is built, so a rename inside the same closure is invisible.
        if (Schema::hasColumn('inventory_transactions', 'reference')
            && ! Schema::hasColumn('inventory_transactions', 'reference_number')) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->renameColumn('reference', 'reference_number');
            });
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transactions', 'reference_number')) {
                $table->string('reference_number', 150)->nullable()->after('type');
            }

            if (! Schema::hasColumn('inventory_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('reference_number');
            }

            if (! Schema::hasColumn('inventory_transactions', 'direction')) {
                $table->string('direction', 10)->nullable()->after('type');
            }

            if (! Schema::hasColumn('inventory_transactions', 'balance_after')) {
                $table->unsignedInteger('balance_after')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('inventory_transactions', 'is_reversed')) {
                $table->boolean('is_reversed')->default(false)->after('notes');
            }
        });

        // Fast filtering by product history and by transaction type/date.
        if (! Schema::hasIndex('inventory_transactions', 'inv_tx_product_created_index')) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->index(['product_id', 'created_at'], 'inv_tx_product_created_index');
            });
        }

        if (! Schema::hasIndex('inventory_transactions', 'inv_tx_type_created_index')) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->index(['type', 'created_at'], 'inv_tx_type_created_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('inv_tx_product_created_index');
            $table->dropIndex('inv_tx_type_created_index');
            $table->dropColumn(['notes', 'direction', 'balance_after', 'is_reversed']);

            if (Schema::hasColumn('inventory_transactions', 'reference_number')) {
                $table->renameColumn('reference_number', 'reference');
            }
        });
    }
};
