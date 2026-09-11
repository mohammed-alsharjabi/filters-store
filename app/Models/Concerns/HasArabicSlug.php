<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use App\Support\ArabicSlugger;

trait HasArabicSlug
{
    public static function bootHasArabicSlug(): void
    {
        static::saving(function ($model): void {
            $source = $model->getAttribute($model->slugSourceColumn());
            $model->slug = ArabicSlugger::unique($model, (string) ($model->slug ?: $source), $model->getKey());
        });
        static::updated(function ($model): void {
            $oldSlug = $model->getOriginal('slug');
            if ($oldSlug && $oldSlug !== $model->slug && $model->shouldCreateSlugRedirect()) {
                Redirect::query()->updateOrCreate(
                    ['old_path' => '/'.trim($model->routePrefix().'/'.$oldSlug, '/')],
                    ['new_path' => '/'.trim($model->routePrefix().'/'.$model->slug, '/'), 'status_code' => 301, 'is_active' => true],
                );
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return array_key_exists('title', $this->getAttributes()) ? 'title' : 'name';
    }

    protected function shouldCreateSlugRedirect(): bool
    {
        return (bool) ($this->is_published ?? $this->is_active ?? false) || ($this->status ?? null) === 'published' || $this->published_at !== null;
    }

    abstract public function routePrefix(): string;
}
