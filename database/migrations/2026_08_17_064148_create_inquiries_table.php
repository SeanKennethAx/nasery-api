<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('event_title')->nullable();

            $table->string('event_type');

            $table->date('event_date');

            $table->string('location');

            $table->unsignedInteger(
                'expected_guests'
            );

            $table->string('budget_range');

            $table->text(
                'additional_details'
            )->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
