<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn(['cost', 'technician', 'status', 'next_service_date']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('technician')->nullable();
            $table->enum('status', ['scheduled', 'completed'])->default('scheduled');
            $table->date('next_service_date')->nullable();
        });
    }
};
