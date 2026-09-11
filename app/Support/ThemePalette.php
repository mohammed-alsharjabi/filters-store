<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class ThemePalette
{
    public function definitions(): array
    {
        return config('theme.colors', []);
    }

    public function defaults(): array
    {
        return collect($this->definitions())->mapWithKeys(
            fn (array $definition, string $key): array => [$key => $definition['default']]
        )->all();
    }

    public function fromSettings(array $settings): array
    {
        return collect($this->definitions())->mapWithKeys(function (array $definition, string $key) use ($settings): array {
            $value = $this->normalize((string) Arr::get($settings, $key, $definition['default']));

            return [$definition['css'] => $value ?: $definition['default']];
        })->all();
    }

    public function css(array $settings): string
    {
        $properties = collect($this->fromSettings($settings))
            ->map(fn (string $value, string $name): string => $name.':'.$value)
            ->implode(';');

        return ':root{'.$properties.'}';
    }

    public function normalize(string $value): ?string
    {
        $value = strtoupper(trim($value));

        return preg_match('/^#[0-9A-F]{6}$/', $value) === 1 ? $value : null;
    }

    public function contrast(string $foreground, string $background): float
    {
        $first = $this->luminance($foreground);
        $second = $this->luminance($background);

        return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($this->normalize($hex) ?: '#000000', '#');
        $channels = array_map(function (string $channel): float {
            $value = hexdec($channel) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
