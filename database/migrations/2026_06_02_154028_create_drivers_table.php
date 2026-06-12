<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->nullable()->unique();
            $table->string('nic', 20)->unique();
            $table->string('licence_number', 30)->unique();
            $table->date('licence_expiry_date');
            $table->string('phone_number', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Link driver login accounts to their driver record now that the table
        // exists (the users table is created earlier).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
        });

        Schema::dropIfExists('drivers');
    }
};
