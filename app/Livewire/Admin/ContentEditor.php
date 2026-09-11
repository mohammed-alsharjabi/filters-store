<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Support\AdminContent;
use App\Support\ResponsiveImageService;
use App\Support\SeoSuggestionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContentEditor extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public string $type;

    public ?int $record = null;

    public array $data = [];

    public array $relations = [];

    public array $seo = [];

    public $image;

    public array $gallery = [];

    public array $imageMetadata = [];

    public function mount(string $type, ?int $record = null): void
    {
        $this->authorize('manage-content');
        $this->type = $type;
        $this->record = $record;
        $definition = AdminContent::get($type);
        foreach ($definition['fields'] as $name => $field) {
            $this->data[$name] = $field['default'] ?? null;
        }
        foreach ($definition['relations'] ?? [] as $key => $relation) {
            $this->relations[$key] = [];
        }
        $this->seo = [
            'meta_title' => null,
            'meta_description' => null,
            'focus_keyword' => null,
            'related_terms' => null,
            'internal_links' => null,
            'canonical_url' => null,
            'robots' => 'noindex,follow',
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'schema_type' => null,
        ];
        if ($record) {
            $model = $definition['model']::query()->findOrFail($record);
            foreach ($definition['fields'] as $name => $field) {
                $value = $model->{$name};
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format(str_contains($field['type'], 'datetime') ? 'Y-m-d\TH:i' : 'Y-m-d');
                }
                $this->data[$name] = $value;
            }
            foreach ($definition['relations'] ?? [] as $key => $relation) {
                $this->relations[$key] = $model->{$relation['relation']}()->pluck('id')->map(fn ($id) => (string) $id)->all();
            }
            if (method_exists($model, 'seo')) {
                $meta = $model->seo()->first();
                $this->seo = array_merge($this->seo, [
                    'meta_title' => $meta?->meta_title, 'meta_description' => $meta?->meta_description,
                    'focus_keyword' => $meta?->focus_keyword, 'related_terms' => $meta?->related_terms,
                    'internal_links' => $meta?->internal_links,
                    'canonical_url' => $meta?->canonical_url, 'robots' => $meta?->robots ?? 'index,follow',
                    'og_title' => $meta?->og_title, 'og_description' => $meta?->og_description,
                    'og_image' => $meta?->og_image, 'schema_type' => $meta?->schema_type,
                ]);
            }
            if ($model instanceof Project) {
                $this->imageMetadata = $model->images()->get()->mapWithKeys(fn (ProjectImage $image): array => [
                    $image->id => ['alt_text' => $image->alt_text, 'caption' => $image->caption],
                ])->all();
            }
        }
        $this->applySeoSuggestions(false);
    }

    public function updatedData(mixed $value, string $key): void
    {
        if (in_array($key, ['name', 'title', 'excerpt', 'description', 'service_category_id', 'article_category_id', 'service_id', 'area_id'], true)) {
            $this->applySeoSuggestions(false);
        }
    }

    public function suggestSeo(): void
    {
        $this->applySeoSuggestions(true);
    }

    public function save()
    {
        $this->authorize('manage-content');
        $definition = AdminContent::get($this->type);
        $this->applySeoSuggestions(false);
        $rules = [];
        foreach ($definition['fields'] as $name => $field) {
            $rules['data.'.$name] = $field['rules'];
        }
        foreach ($definition['relations'] ?? [] as $key => $relation) {
            $rules['relations.'.$key] = ['array'];
            $rules['relations.'.$key.'.*'] = ['integer', 'exists:'.(new $relation['model'])->getTable().',id'];
        }
        if ($definition['image'] ?? false) {
            $rules['image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=300,max_width=8000,max_height=8000'];
        }
        if ($definition['gallery'] ?? false) {
            $rules['gallery'] = ['array', 'max:20'];
            $rules['gallery.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=300,max_width=8000,max_height=8000'];
        }
        $rules['seo.meta_title'] = ['nullable', 'string', 'max:70'];
        $rules['seo.meta_description'] = ['nullable', 'string', 'max:170'];
        $rules['seo.focus_keyword'] = ['nullable', 'string', 'max:255'];
        $rules['seo.related_terms'] = ['nullable', 'string', 'max:10000'];
        $rules['seo.internal_links'] = ['nullable', 'string', 'max:20000'];
        $rules['seo.canonical_url'] = ['nullable', 'url', 'max:2048'];
        $rules['seo.robots'] = ['nullable', 'string', 'max:80'];
        $rules['seo.og_title'] = ['nullable', 'string', 'max:100'];
        $rules['seo.og_description'] = ['nullable', 'string', 'max:200'];
        $rules['seo.og_image'] = ['nullable', 'string', 'max:2048'];
        $rules['seo.schema_type'] = ['nullable', 'string', 'max:100'];
        $this->validate($rules);

        if (in_array($this->type, ['services', 'areas', 'articles', 'projects'], true)) {
            if (($this->data['status'] ?? 'draft') === 'draft') {
                $this->data['published_at'] = null;
                $this->seo['robots'] = 'noindex,follow';
            } elseif (blank($this->data['published_at'] ?? null)) {
                $this->data['published_at'] = now()->format('Y-m-d\TH:i');
                if (($this->seo['robots'] ?? '') === 'noindex,follow') {
                    $this->seo['robots'] = 'index,follow,max-image-preview:large';
                }
            }
        }

        if ($this->type === 'projects') {
            $this->data['is_published'] = ($this->data['status'] ?? 'draft') === 'published';
        }
        if (in_array($this->type, ['services', 'areas'], true) && ($this->data['status'] ?? 'draft') === 'published') {
            $this->data['is_active'] = true;
        }

        if (in_array($this->type, ['services', 'materials'], true) && ($this->data['is_price_published'] ?? false) && blank($this->data['price_from'] ?? null)) {
            $this->addError('data.price_from', 'أدخل سعرًا معتمدًا قبل تفعيل نشر السعر.');

            return null;
        }
        $model = $this->record ? $definition['model']::query()->findOrFail($this->record) : new $definition['model'];
        $model->fill($this->data);
        $previousFeaturedImage = null;
        if (($definition['image'] ?? false) && $this->image) {
            $previousFeaturedImage = $model->featured_image;
            $model->featured_image = app(ResponsiveImageService::class)->storePrimaryImage($this->image);
        }
        $model->save();
        if ($previousFeaturedImage) {
            Storage::disk('public')->delete($previousFeaturedImage);
        }
        if (in_array($this->type, ['services', 'service-categories'], true)) {
            Cache::forget('navigation.service-categories');
        }
        if ($this->type === 'projects') {
            Cache::forget('navigation.has-published-projects');
        }
        $this->record = $model->id;
        foreach ($definition['relations'] ?? [] as $key => $relation) {
            $model->{$relation['relation']}()->sync($this->relations[$key] ?? []);
        }
        if (($definition['image'] ?? false) && $model->featured_image && blank($this->seo['og_image'] ?? null)) {
            $this->seo['og_image'] = $model->featured_image;
        }
        if (method_exists($model, 'seo')) {
            $model->seo()->updateOrCreate([], array_filter($this->seo, fn ($value) => $value !== null));
        }
        if ($model instanceof Project) {
            foreach ($this->imageMetadata as $imageId => $metadata) {
                $model->images()->whereKey($imageId)->update([
                    'alt_text' => filled($metadata['alt_text'] ?? null) ? $metadata['alt_text'] : $model->title,
                    'caption' => $metadata['caption'] ?? null,
                ]);
            }
        }
        if (($definition['gallery'] ?? false) && $this->gallery) {
            $start = (int) $model->images()->max('sort_order');
            $areaName = $model instanceof Project ? $model->area?->name : null;
            foreach ($this->gallery as $index => $file) {
                $processed = app(ResponsiveImageService::class)->storeProjectImage($file);
                $alt = app(SeoSuggestionService::class)->imageAlt($model->title, $areaName, 'صورة مشروع');
                $image = $model->images()->create($processed + ['alt_text' => $alt, 'caption' => $model->title.($areaName ? ' في '.$areaName : ''), 'stage' => 'gallery', 'sort_order' => $start + $index + 1, 'is_cover' => ! $model->images()->exists()]);
                $this->imageMetadata[$image->id] = ['alt_text' => $image->alt_text, 'caption' => $image->caption];
            }
            $this->gallery = [];
        }
        session()->flash('success', 'حُفظ المحتوى بنجاح.');

        return redirect()->route('admin.content.edit', ['type' => $this->type, 'record' => $model->id]);
    }

    public function deleteImage(int $imageId): void
    {
        $this->authorize('manage-content');
        abort_unless($this->type === 'projects' && $this->record, 404);
        $image = ProjectImage::where('project_id', $this->record)->findOrFail($imageId);
        app(ResponsiveImageService::class)->deleteProjectImage($image->toArray());
        $image->delete();
    }

    public function updateImageStage(int $imageId, string $stage): void
    {
        $this->authorize('manage-content');
        abort_unless($this->type === 'projects' && $this->record, 404);
        abort_unless(in_array($stage, ['gallery', 'before', 'after'], true), 422);
        ProjectImage::query()->where('project_id', $this->record)->findOrFail($imageId)->update(['stage' => $stage]);
    }

    public function setCover(int $imageId): void
    {
        $this->authorize('manage-content');
        abort_unless($this->type === 'projects' && $this->record, 404);
        ProjectImage::query()->where('project_id', $this->record)->update(['is_cover' => false]);
        ProjectImage::query()->where('project_id', $this->record)->findOrFail($imageId)->update(['is_cover' => true]);
    }

    public function render()
    {
        $definition = AdminContent::get($this->type);
        $options = [];
        foreach ($definition['fields'] as $name => $field) {
            if (($field['type'] ?? '') === 'select') {
                $options[$name] = $field['model']::query()->orderBy($field['model'] === Project::class ? 'title' : 'name')->get(['id', $field['model'] === Project::class ? 'title' : 'name']);
            }
        }
        foreach ($definition['relations'] ?? [] as $key => $relation) {
            $title = $relation['title'] ?? ($relation['model'] === Article::class ? 'title' : 'name');
            $options[$key] = $relation['model']::query()->when($this->record && $relation['model'] === $definition['model'], fn ($q) => $q->whereKeyNot($this->record))->orderBy($title)->get(['id', $title]);
        }
        $model = $this->record ? $definition['model']::query()->find($this->record) : null;
        $seoWarnings = app(SeoSuggestionService::class)->warnings($this->seo, $model);

        return view('livewire.admin.content-editor', compact('definition', 'options', 'model', 'seoWarnings'))->layout('components.layouts.admin', ['title' => ($this->record ? 'تعديل ' : 'إضافة ').$definition['label']]);
    }

    private function applySeoSuggestions(bool $force): void
    {
        if (! in_array($this->type, ['services', 'projects', 'articles', 'areas', 'service-categories'], true)) {
            return;
        }

        $definition = AdminContent::get($this->type);
        $model = $this->record ? $definition['model']::query()->find($this->record) : null;
        $suggestions = app(SeoSuggestionService::class)->suggest($this->type, $this->data, $model);
        if (! $suggestions) {
            return;
        }

        if ($force || blank($this->data['slug'] ?? null)) {
            $this->data['slug'] = $suggestions['slug'];
        }
        foreach (['featured_image_alt', 'featured_image_caption'] as $field) {
            if (array_key_exists($field, $this->data) && ($force || blank($this->data[$field] ?? null))) {
                $this->data[$field] = $suggestions[$field];
            }
        }
        foreach (['meta_title', 'meta_description', 'focus_keyword', 'related_terms', 'internal_links', 'canonical_url', 'og_title', 'og_description', 'schema_type'] as $field) {
            if ($force || blank($this->seo[$field] ?? null)) {
                $this->seo[$field] = $suggestions[$field];
            }
        }
    }
}
