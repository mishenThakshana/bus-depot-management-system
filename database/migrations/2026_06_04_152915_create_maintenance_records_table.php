<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $table->string('maintenance_type');
            $table->text('description');
            $table->date('serviced_date');
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('technician')->nullable();
            $table->enum('status', ['scheduled', 'completed'])->default('scheduled');
            $table->date('next_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
