<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('gyms')
            ->select(['id', 'next_trainer_sequence'])
            ->orderBy('id')
            ->each(function (object $gym): void {
                $trainers = DB::table('trainers')
                    ->select(['id', 'trainer_code'])
                    ->where('gym_id', $gym->id)
                    ->orderBy('id')
                    ->get();
                $usedSequences = [];

                foreach ($trainers as $trainer) {
                    if (
                        is_string($trainer->trainer_code)
                        && preg_match('/^TRN-(\d{6})$/', $trainer->trainer_code, $matches) === 1
                    ) {
                        $usedSequences[(int) $matches[1]] = true;
                    }
                }

                $nextSequence = max(1, (int) $gym->next_trainer_sequence);

                foreach ($trainers as $trainer) {
                    if ($trainer->trainer_code !== null) {
                        continue;
                    }

                    while (isset($usedSequences[$nextSequence])) {
                        $nextSequence++;
                    }

                    DB::table('trainers')
                        ->where('id', $trainer->id)
                        ->update(['trainer_code' => sprintf('TRN-%06d', $nextSequence)]);
                    $usedSequences[$nextSequence] = true;
                    $nextSequence++;
                }

                DB::table('gyms')
                    ->where('id', $gym->id)
                    ->update(['next_trainer_sequence' => $nextSequence]);
            });

        DB::table('trainer_members')
            ->whereNull('assigned_at')
            ->update(['assigned_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Generated trainer codes and assignment timestamps are durable history.
    }
};
