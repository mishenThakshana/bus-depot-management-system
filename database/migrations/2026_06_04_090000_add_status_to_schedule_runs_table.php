<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_runs', function (Blueprint $table) {
            // 'scheduled' runs are live and block other schedules on that slot;
            // 'cancelled' runs are kept for history, ignored by clash checks,
            // and can be reactivated.
            $table->string('status')->default('scheduled')->after('run_date')->index();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_runs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
