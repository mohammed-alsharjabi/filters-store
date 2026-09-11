<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Support\SettingsRepository;
use App\Support\ThemePalette;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class SettingsEditor extends Component
{
    use AuthorizesRequests;

    public array $values = [];

    public function mount(): void
    {
        $this->authorize('manage-content');
        $this->values = Setting::query()->pluck('value', 'key')->all();
    }

    public function save(): void
    {
        $this->authorize('manage-content');
        $allowed = Setting::query()->pluck('key')->all();
        $themeRules = collect(config('theme.colors', []))->mapWithKeys(
            fn (array $definition, string $key): array => ['values.'.$key => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']]
        )->all();

        $this->validate([
            'values' => ['array'], 'values.*' => ['nullable', 'string', 'max:5000'],
            'values.site_name' => ['required', 'string', 'max:120'],
            'values.phone_display' => ['required', 'string', 'max:30'],
            'values.phone_e164' => ['required', 'regex:/^\+9665\d{8}$/'],
            'values.phone_tel' => ['required', 'regex:/^tel:\+9665\d{8}$/'],
            'values.whatsapp_url' => ['required', 'regex:#^https://wa\.me/9665\d{8}$#'],
            'values.address' => ['required', 'string', 'max:255'],
            'values.city' => ['required', 'string', 'max:100'],
            'values.country' => ['required', 'string', 'max:100'],
            'values.search_console_verification' => ['nullable', 'regex:/^[A-Za-z0-9_-]{10,200}$/'],
            'values.ga_measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]{5,20}$/'],
            'values.logo_url' => ['nullable', 'url', 'max:2048'],
            ...$themeRules,
        ], [
            'values.*.required' => 'هذا الحقل مطلوب.',
            'values.*.regex' => 'أدخل لونًا بصيغة HEX كاملة مثل #623992.',
        ]);

        if (! $this->themeContrastIsValid()) {
            return;
        }

        foreach ($this->values as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $normalized = array_key_exists($key, config('theme.colors', []))
                    ? app(ThemePalette::class)->normalize((string) $value)
                    : $value;
                Setting::query()->where('key', $key)->update(['value' => $normalized]);
            }
        }
        app(SettingsRepository::class)->clear();
        session()->flash('success', 'حُفظت الإعدادات.');
    }

    public function resetAppearance(): void
    {
        $this->authorize('manage-content');

        foreach (app(ThemePalette::class)->defaults() as $key => $value) {
            Setting::query()->where('key', $key)->update(['value' => $value]);
            $this->values[$key] = $value;
        }

        app(SettingsRepository::class)->clear();
        session()->flash('success', 'استُعيدت ألوان الهوية الافتراضية.');
    }

    public function render()
    {
        return view('livewire.admin.settings-editor', ['groups' => Setting::query()->orderBy('group')->orderBy('id')->get()->groupBy('group')])
            ->layout('components.layouts.admin', ['title' => 'إعدادات النشاط']);
    }

    private function themeContrastIsValid(): bool
    {
        $palette = app(ThemePalette::class);
        $pairs = [
            ['theme_primary_content', 'theme_primary', 'تباين نص الأزرار مع اللون الأساسي أقل من معيار WCAG AA (4.5:1).'],
            ['theme_text', 'theme_background', 'تباين النص مع خلفية الموقع أقل من معيار WCAG AA (4.5:1).'],
            ['theme_text', 'theme_base_100', 'تباين النص مع خلفية البطاقات أقل من معيار WCAG AA (4.5:1).'],
        ];
        $valid = true;

        foreach ($pairs as [$foreground, $background, $message]) {
            if ($palette->contrast((string) ($this->values[$foreground] ?? ''), (string) ($this->values[$background] ?? '')) < 4.5) {
                $this->addError('values.'.$foreground, $message);
                $valid = false;
            }
        }

        return $valid;
    }
}
