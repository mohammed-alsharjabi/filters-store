<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> القيمة الجديدة ثم السابقة */
    private array $contacts = [
        'phone_display' => ['050 943 9667', '050 866 7812'],
        'phone_e164' => ['+966509439667', '+966508667812'],
        'phone_tel' => ['tel:+966509439667', 'tel:+966508667812'],
        'whatsapp_url' => ['https://wa.me/966509439667', 'https://wa.me/966508667812'],
    ];

    public function up(): void
    {
        $this->apply(0);
    }

    public function down(): void
    {
        $this->apply(1);
    }

    private function apply(int $index): void
    {
        $labels = [
            'phone_display' => 'رقم التواصل الظاهر',
            'phone_e164' => 'رقم التواصل الدولي',
            'phone_tel' => 'رابط الاتصال',
            'whatsapp_url' => 'رابط واتساب',
        ];
        $now = now();

        foreach ($this->contacts as $key => $values) {
            $updated = DB::table('settings')->where('key', $key)->update([
                'value' => $values[$index],
                'updated_at' => $now,
            ]);

            if ($updated === 0) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $values[$index],
                    'type' => 'string',
                    'group' => 'business',
                    'label' => $labels[$key],
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }
};
