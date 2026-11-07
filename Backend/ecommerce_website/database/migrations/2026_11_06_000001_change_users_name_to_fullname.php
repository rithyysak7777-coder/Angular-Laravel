<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add fullName column if not exists
        if (!Schema::hasColumn('users', 'fullName')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('fullName', 200)->nullable()->after('role_id');
            });
        }

        // 2. Populate fullName from first_name and last_name if they exist
        if (Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
            DB::statement("UPDATE users SET \"fullName\" = TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) WHERE \"fullName\" IS NULL OR \"fullName\" = ''");
            
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['first_name', 'last_name']);
            });
        }

        // 3. Make fullName not null with default
        DB::statement('UPDATE users SET "fullName" = \'User\' WHERE "fullName" IS NULL OR "fullName" = \'\'');
        DB::statement('ALTER TABLE users ALTER COLUMN "fullName" SET NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('users', 'first_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
            });

            DB::statement("UPDATE users SET first_name = split_part(\"fullName\", ' ', 1), last_name = substr(\"fullName\", length(split_part(\"fullName\", ' ', 1)) + 2)");
        }

        if (Schema::hasColumn('users', 'fullName')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fullName');
            });
        }
    }
};

