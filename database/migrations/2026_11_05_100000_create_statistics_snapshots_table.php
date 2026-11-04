<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per state per day: its mill count, how complete its mill data is, and its mill types.
 * Statistics charts read these to show how a state has changed over time, which the mills table alone can't
 * (it only knows each mill as it is now). Written by `statistics:snapshot` (scheduled daily) and by the
 * Statistics pages when today's row is missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('captured_on');
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->json('metrics');
            $table->timestamps();

            $table->unique(['captured_on', 'state_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistics_snapshots');
    }
};
