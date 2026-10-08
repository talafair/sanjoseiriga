<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'auditable_id')) {
            return;
        }

        if (Schema::getColumnType('audit_logs', 'auditable_id') === 'string') {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('auditable_id', 255)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'auditable_id')) {
            return;
        }

        if (Schema::getColumnType('audit_logs', 'auditable_id') !== 'string') {
            return;
        }

        foreach (DB::table('audit_logs')->select('auditable_id')->cursor() as $row) {
            $id = (string) $row->auditable_id;

            if (! ctype_digit($id) || strlen($id) > 20 || (strlen($id) === 20 && strcmp($id, '18446744073709551615') > 0)) {
                throw new RuntimeException('Cannot roll back audit_logs.auditable_id to an unsigned integer while non-numeric or out-of-range IDs exist.');
            }
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('auditable_id')->change();
        });
    }
};