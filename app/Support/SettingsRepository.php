<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsRepository
{
    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return config('site.fallback', []);
        }

        return Cache::remember('site.settings.all', now()->addMinutes(30), fn (): array => Setting::query()->get()->mapWithKeys(fn (Setting $setting): array => [$setting->key => $this->cast($setting->value, $setting->type)]
        )->all()
        ) + config('site.fallback', []);
    }

    public function public(): array
    {
        if (! Schema::hasTable('settings')) {
            return config('site.fallback', []);
        }

        return Cache::remember('site.settings.public', now()->addMinutes(30), fn (): array => Setting::query()->where('is_public', true)->get()->mapWithKeys(
            fn (Setting $setting): array => [$setting->key => $this->cast($setting->value, $setting->type)]
        )->all() + config('site.fallback', [])
        );
    }

    public function clear(): void
    {
        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $value,
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
