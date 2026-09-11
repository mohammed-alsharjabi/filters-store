@php
    $basicNames = $definition['basic_fields'] ?? array_keys($definition['fields']);
    $imageMetaNames = $definition['image_meta_fields'] ?? [];
    $basicFields = collect($definition['fields'])->only($basicNames);
    $advancedFields = collect($definition['fields'])->except([...$basicNames, ...$imageMetaNames, 'slug']);
@endphp

<div class="content-editor-shell">
<form wire:submit="save" class="space-y-7">
    @if($errors->any())
        <div class="notice-error" role="alert"><strong>لم يتم الحفظ بعد.</strong><span>راجع الحقول المعلّمة أدناه ثم حاول مرة أخرى.</span></div>
    @endif

    <section class="admin-panel admin-primary-panel">
        <div class="admin-panel-head"><div><p class="admin-step">الخطوة 1</p><h2>المعلومات الأساسية</h2><small>هذه الحقول تكفي لإنشاء مسودة جديدة.</small></div><a href="{{ route('admin.content.index',$type) }}">العودة للقائمة</a></div>
        <div class="admin-form-grid">
            @foreach($basicFields as $name => $field)
                @include('livewire.admin.partials.field', ['name' => $name, 'field' => $field])
            @endforeach
        </div>
    </section>

    @if(($definition['image'] ?? false) || ($definition['gallery'] ?? false))
        <section class="admin-panel">
            <div class="admin-panel-head"><div><p class="admin-step">الخطوة 2</p><h2>{{ $definition['gallery'] ?? false ? 'صور المشروع' : 'الصورة الرئيسية' }}</h2><small>سيُقترح النص البديل والتعليق تلقائيًا ويمكن تعديلهما.</small></div></div>

            @if($definition['image'] ?? false)
                @if($model?->featured_image)<figure class="admin-featured-preview"><img class="admin-preview" src="{{ asset('storage/'.$model->featured_image) }}" alt="{{ $model->featured_image_alt }}"><figcaption>{{ $model->featured_image_caption }}</figcaption></figure>@endif
                <input type="file" wire:model="image" accept="image/jpeg,image/png,image/webp">
                <p class="field-help">JPG أو PNG أو WebP، بحد أقصى 5MB وأبعاد لا تقل عن 400×300.</p>
                @error('image')<small class="field-error">{{ $message }}</small>@enderror
                @if($imageMetaNames)
                    <div class="admin-form-grid image-meta-grid">
                        @foreach(collect($definition['fields'])->only($imageMetaNames) as $name => $field)
                            @include('livewire.admin.partials.field', ['name' => $name, 'field' => $field])
                        @endforeach
                    </div>
                @endif
            @endif

            @if($definition['gallery'] ?? false)
                @if($model && $model->images->isNotEmpty())
                    <div class="admin-gallery">
                        @foreach($model->images as $item)
                            <figure>
                                <img src="{{ asset('storage/'.$item->path) }}" alt="{{ $item->alt_text }}">
                                <label><span>النص البديل</span><input type="text" wire:model.blur="imageMetadata.{{ $item->id }}.alt_text" maxlength="255"></label>
                                <label><span>التعليق</span><textarea wire:model.blur="imageMetadata.{{ $item->id }}.caption" rows="2" maxlength="1000"></textarea></label>
                                <select aria-label="نوع الصورة" wire:change="updateImageStage({{ $item->id }}, $event.target.value)"><option value="gallery" @selected($item->stage==='gallery')>معرض</option><option value="before" @selected($item->stage==='before')>قبل</option><option value="after" @selected($item->stage==='after')>بعد</option></select>
                                <button type="button" class="cover-button" wire:click="setCover({{ $item->id }})">{{ $item->is_cover ? 'صورة الغلاف' : 'تعيين كغلاف' }}</button>
                                <button type="button" wire:click="deleteImage({{ $item->id }})" wire:confirm="حذف الصورة؟">حذف</button>
                            </figure>
                        @endforeach
                    </div>
                @endif
                <input type="file" wire:model="gallery" multiple accept="image/jpeg,image/png,image/webp">
                <p class="field-help">حتى 20 صورة، بحد أقصى 5MB للصورة. تُنشأ نسخ WebP وAVIF متجاوبة تلقائيًا.</p>
                @error('gallery.*')<small class="field-error">{{ $message }}</small>@enderror
            @endif
        </section>
    @endif

    @if($advancedFields->isNotEmpty())
        <details class="admin-panel admin-disclosure">
            <summary><span><strong>تفاصيل إضافية</strong><small>المحتوى الكامل، الفيديو، الأسعار، والترتيب</small></span><b>فتح</b></summary>
            <div class="admin-form-grid">
                @foreach($advancedFields as $name => $field)
                    @include('livewire.admin.partials.field', ['name' => $name, 'field' => $field])
                @endforeach
            </div>
        </details>
    @endif

    @foreach($definition['relations'] ?? [] as $key => $relation)
        <details class="admin-panel admin-disclosure">
            <summary><span><strong>{{ $relation['label'] }}</strong><small>اختياري لتحسين الروابط الداخلية والتنقل</small></span><b>فتح</b></summary>
            <div class="checkbox-grid">@foreach($options[$key] as $option)@php($title=$option->{$relation['title'] ?? ($relation['model']===\App\Models\Article::class?'title':'name')})<label><input type="checkbox" wire:model="relations.{{ $key }}" value="{{ $option->id }}"> {{ $title }}</label>@endforeach</div>
            @error('relations.'.$key)<small class="field-error">{{ $message }}</small>@enderror
        </details>
    @endforeach

    @if(($definition['seo'] ?? true) && $type!=='faqs' && $type!=='testimonials')
        <details class="admin-panel admin-disclosure seo-advanced">
            <summary><span><strong>إعدادات SEO المتقدمة</strong><small>اختيارية؛ يقترحها النظام تلقائيًا ويمكن تعديلها يدويًا</small></span><b>فتح</b></summary>
            <div class="seo-suggestion-bar"><div><strong>اقتراحات تلقائية جاهزة</strong><small>لا تُستخدم الكلمة المستهدفة كوسم meta keywords في HTML.</small></div><button class="button button-outline" type="button" wire:click="suggestSeo">إعادة توليد الاقتراحات</button></div>
            @if($seoWarnings)<div class="seo-warnings" role="status">@foreach($seoWarnings as $warning)<p>⚠ {{ $warning }}</p>@endforeach</div>@endif
            <div class="admin-form-grid">
                @if(isset($definition['fields']['slug']))<label><span>Slug المقترح</span><input type="text" wire:model.blur="data.slug" maxlength="255" dir="ltr">@error('data.slug')<small>{{ $message }}</small>@enderror</label>@endif
                <label><span>Meta Title</span><input type="text" wire:model.blur="seo.meta_title" maxlength="70"><small class="counter-help">حتى 70 حرفًا</small>@error('seo.meta_title')<small>{{ $message }}</small>@enderror</label>
                <label class="full-field"><span>Meta Description</span><textarea wire:model.blur="seo.meta_description" rows="3" maxlength="170"></textarea>@error('seo.meta_description')<small>{{ $message }}</small>@enderror</label>
                <label><span>Focus Keyword — داخلي فقط</span><input type="text" wire:model.blur="seo.focus_keyword" maxlength="255">@error('seo.focus_keyword')<small>{{ $message }}</small>@enderror</label>
                <label><span>Schema المقترح</span><input type="text" wire:model.blur="seo.schema_type" maxlength="100" dir="ltr"></label>
                <label class="full-field"><span>كلمات وعبارات مرتبطة — سطر لكل عبارة</span><textarea wire:model.blur="seo.related_terms" rows="4"></textarea></label>
                <label class="full-field"><span>روابط داخلية مناسبة — عنوان | رابط</span><textarea wire:model.blur="seo.internal_links" rows="4" dir="auto"></textarea></label>
                <label class="full-field"><span>Canonical URL</span><input type="url" wire:model.blur="seo.canonical_url" dir="ltr">@error('seo.canonical_url')<small>{{ $message }}</small>@enderror</label>
                <label><span>Robots</span><input type="text" wire:model.blur="seo.robots" dir="ltr"></label>
                <label><span>عنوان Open Graph</span><input type="text" wire:model.blur="seo.og_title" maxlength="100"></label>
                <label class="full-field"><span>وصف Open Graph</span><textarea wire:model.blur="seo.og_description" rows="3" maxlength="200"></textarea></label>
                <label class="full-field"><span>مسار صورة Open Graph</span><input type="text" wire:model.blur="seo.og_image" dir="ltr" placeholder="content/image.webp"></label>
            </div>
        </details>
    @endif

    <div class="admin-save-bar"><button class="button button-primary" type="submit" wire:loading.attr="disabled">حفظ المحتوى</button><span wire:loading>جارٍ الحفظ…</span><small>يمكن حفظ المحتوى كمسودة والعودة إليه لاحقًا.</small></div>
</form>

@if($type === 'services' && $model)
    <livewire:admin.service-image-manager :service="$model" :key="'service-images-'.$model->id" />
@endif
</div>
