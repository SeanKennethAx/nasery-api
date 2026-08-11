<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'firstname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('firstname')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'middlename')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('middlename')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'lastname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('lastname')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->nullable();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
