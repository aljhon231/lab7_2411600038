<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 30)->default('regular_user')->index();
            });
        }

        if (! Schema::hasColumn('products', 'package_size')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->unsignedInteger('package_size')->default(1);
            });
        }

        if (! Schema::hasColumn('suppliers', 'minimum_order_quantity')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->unsignedInteger('minimum_order_quantity')->default(1);
            });
        }

        \Illuminate\Support\Facades\DB::table('users')
            ->where('is_admin', true)
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }

        if (Schema::hasColumn('products', 'package_size')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('package_size');
            });
        }

        if (Schema::hasColumn('suppliers', 'minimum_order_quantity')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->dropColumn('minimum_order_quantity');
            });
        }
    }
};
