<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class ArabicSlugger
{
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value) ?? $value;
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ؤ', 'ئ', 'ة'], ['ا', 'ا', 'ا', 'ي', 'و', 'ي', 'ه'], $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
        $value = trim(preg_replace('/-+/u', '-', $value) ?? '', '-');

        return $value !== '' ? $value : 'صفحة';
    }

    public static function unique(Model $model, string $value, ?int $ignoreId = null): string
    {
        $base = self::normalize($value);
        $slug = $base;
        $suffix = 2;
        while ($model->newQuery()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
