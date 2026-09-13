<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('resident', 'guest', 'official', 'superadmin') NOT NULL DEFAULT 'resident'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'superadmin')->update(['role' => 'official']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('resident', 'guest', 'official') NOT NULL DEFAULT 'resident'");
        }
    }
};