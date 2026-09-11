<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('event_preparation_items', function (Blueprint $table) {
            $table->foreignId('assigned_team_member_id')->nullable()->after('label')->constrained('team_members')->nullOnDelete();
            $table->text('completion_note')->nullable()->after('is_completed');
            $table->timestamp('completed_at')->nullable()->after('completion_note');
            $table->foreignId('completed_by_user_id')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->string('review_status', 20)->default('pending')->after('completed_by_user_id');
            $table->timestamp('reviewed_at')->nullable()->after('review_status');
        });
    }

    public function down(): void
    {
        Schema::table('event_preparation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_team_member_id');
            $table->dropConstrainedForeignId('completed_by_user_id');
            $table->dropColumn(['completion_note', 'completed_at', 'review_status', 'reviewed_at']);
        });
        Schema::dropIfExists('team_members');
    }
};
