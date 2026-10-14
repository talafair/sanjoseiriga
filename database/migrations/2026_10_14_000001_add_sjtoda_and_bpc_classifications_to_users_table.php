<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_sjtoda')) {
                $table->boolean('is_sjtoda')->default(false)->after('is_lgbtqia');
            }
            if (! Schema::hasColumn('users', 'is_bpc')) {
                $table->boolean('is_bpc')->default(false)->after('is_sjtoda');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['is_sjtoda', 'is_bpc'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
