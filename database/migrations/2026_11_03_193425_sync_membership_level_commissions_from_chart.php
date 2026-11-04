<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align the financial membership rates with the values saved on the admin chart.
     */
    public function up(): void
    {
        $settings = DB::table('platform_settings')->first();

        if (! $settings || blank($settings->membership_chart)) {
            return;
        }

        $chart = json_decode($settings->membership_chart, true);

        if (! is_array($chart)) {
            return;
        }

        $levels = DB::table('membership_levels')
            ->get()
            ->keyBy(fn (object $level): string => strtolower($level->name));

        foreach ($chart as $row) {
            if (! is_array($row) || ! isset($row['name'], $row['commission'])) {
                continue;
            }

            $level = $levels->get(strtolower(trim((string) $row['name'])));
            $percentage = preg_replace('/[^0-9.\-]/', '', (string) $row['commission']);

            if ($level === null || $percentage === null || $percentage === '') {
                continue;
            }

            DB::table('membership_levels')
                ->where('id', $level->id)
                ->update([
                    'percentage' => number_format((float) $percentage, 2, '.', ''),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
