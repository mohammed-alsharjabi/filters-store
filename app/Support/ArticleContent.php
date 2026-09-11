<?php

namespace App\Support;

use Illuminate\Support\Str;

final class ArticleContent
{
    public function sections(?string $body): array
    {
        $blocks = preg_split('/\R{2,}/u', trim((string) $body)) ?: [];
        $used = [];

        return collect($blocks)->map(function (string $block, int $index) use (&$used): ?array {
            $lines = collect(preg_split('/\R/u', trim($block)) ?: [])->map('trim')->filter()->values();
            if ($lines->isEmpty()) {
                return null;
            }

            $hasHeading = $lines->count() > 1;
            $title = $hasHeading ? (string) $lines->shift() : null;
            $base = Str::slug($title ?: 'فقرة-'.$index, '-', 'ar') ?: 'section-'.($index + 1);
            $occurrence = ($used[$base] ?? 0) + 1;
            $used[$base] = $occurrence;

            return [
                'id' => $occurrence > 1 ? $base.'-'.$occurrence : $base,
                'title' => $title,
                'paragraphs' => $lines->isNotEmpty() ? $lines->all() : [trim($block)],
            ];
        })->filter()->values()->all();
    }

    public function readingMinutes(?string $body): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags((string) $body)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / 180));
    }
}
