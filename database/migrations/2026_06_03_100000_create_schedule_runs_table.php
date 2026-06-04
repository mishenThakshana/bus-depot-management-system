<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->date('run_date')->index();
            $table->string('status')->default('scheduled')->index();
            $table->timestamps();

            // One run per schedule per date.
            $table->unique(['schedule_id', 'run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_runs');
    }
};
