<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false);
            });
        }

        if (! Schema::hasColumn('products', 'low_stock_notified')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('low_stock_notified')->default(false);
            });
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transactions', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }

            if (! Schema::hasColumn('inventory_transactions', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('inventory_transactions', 'reason')) {
                $table->string('reason')->nullable();
            }

            if (! Schema::hasColumn('inventory_transactions', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->string('type')->change();
            $table->integer('quantity')->change();
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')
            ->where('email', 'admin@example.com')
            ->update(['is_admin' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['reference_number', 'notes', 'reason']);
            $table->enum('type', ['stock_in', 'stock_out'])->change();
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('low_stock_notified');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
