<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keep stored membership copy in step with the uniform 30-product sets.
     */
    public function up(): void
    {
        $settings = DB::table('platform_settings')->first();

        if (! $settings) {
            return;
        }

        $levels = ['Normal', 'Silver', 'Gold', 'Platinum', 'Diamond', 'VVIP'];
        $perks = $settings->membership_perks ? json_decode($settings->membership_perks, true) : null;

        if (is_array($perks)) {
            foreach ($levels as $level) {
                if (! isset($perks[$level]) || ! is_array($perks[$level])) {
                    $perks[$level] = $this->defaultPerks($level);

                    continue;
                }

                $perks[$level] = array_map(
                    fn (mixed $perk): mixed => is_string($perk)
                        ? preg_replace('/(per Product\s*-\s*)\d+(\s*products per set)/i', '${1}30${2}', $perk)
                        : $perk,
                    $perks[$level],
                );
            }

            DB::table('platform_settings')
                ->where('id', $settings->id)
                ->update(['membership_perks' => json_encode($perks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }

        $chart = $settings->membership_chart ? json_decode($settings->membership_chart, true) : null;

        if (is_array($chart)) {
            foreach ($chart as $index => $row) {
                if (is_array($row)) {
                    $chart[$index]['tasks'] = 30;
                }
            }

            DB::table('platform_settings')
                ->where('id', $settings->id)
                ->update(['membership_chart' => json_encode($chart, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    /**
     * @return list<string>
     */
    private function defaultPerks(string $level): array
    {
        $percentage = DB::table('membership_levels')->where('name', $level)->value('percentage') ?? '2.00';

        return [
            $level.' users are assigned general usage access to data collection',
            'Applicable to most data collection situations of light to medium level of usage involving the Products',
            'Profits of '.$percentage.'% per Product - 30 products per set',
            'No access to other premium features',
        ];
    }
};
