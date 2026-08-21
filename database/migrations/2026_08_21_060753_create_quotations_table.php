<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inquiry_id')
                ->constrained('inquiries')
                ->cascadeOnDelete();

            $table->foreignId('organizer_id')
                ->constrained('organizers')
                ->cascadeOnDelete();

            $table->decimal('quotation_amount', 12, 2);

            $table->string('package_name')
                ->nullable();

            $table->string('timeline')
                ->nullable();

            $table->text('quotation_details')
                ->nullable();

            $table->enum('quotation_status', [
                'pending',
                'accepted',
                'rejected',
                'withdrawn',
            ])->default('pending');

            $table->timestamps();

            $table->unique([
                'inquiry_id',
                'organizer_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
