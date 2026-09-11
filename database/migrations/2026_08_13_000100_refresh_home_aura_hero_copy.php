<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'hero_eyebrow' => [
                ['حلول هندسية للمساحات الخارجية', 'حلول تظليل وتنفيذ احترافي في الرياض'],
                'هندسة المساحات الخارجية',
            ],
            'hero_title' => [
                ['نصمم الظل كجزء من المكان', 'نصمم المساحة التي تستحقها'],
                'نصنع الظل الذي يغيّر المكان',
            ],
            'hero_description' => [
                ['مظلات وسواتر وهياكل مصممة لمناخ الرياض ومساحة مشروعك.', 'مظلات وسواتر وبرجولات بتصميم هندسي وتنفيذ يوازن بين الجمال والمتانة.'],
                'مظلات وسواتر وبرجولات بتصميم هندسي وتنفيذ يليق بمشروعك.',
            ],
        ];

        foreach ($replacements as $key => [$oldValues, $newValue]) {
            Setting::query()->where('key', $key)->whereIn('value', $oldValues)->update(['value' => $newValue]);
        }
    }

    public function down(): void
    {
        $replacements = [
            'hero_eyebrow' => ['هندسة المساحات الخارجية', 'حلول هندسية للمساحات الخارجية'],
            'hero_title' => ['نصنع الظل الذي يغيّر المكان', 'نصمم الظل كجزء من المكان'],
            'hero_description' => ['مظلات وسواتر وبرجولات بتصميم هندسي وتنفيذ يليق بمشروعك.', 'مظلات وسواتر وهياكل مصممة لمناخ الرياض ومساحة مشروعك.'],
        ];

        foreach ($replacements as $key => [$newValue, $oldValue]) {
            Setting::query()->where('key', $key)->where('value', $newValue)->update(['value' => $oldValue]);
        }
    }
};
