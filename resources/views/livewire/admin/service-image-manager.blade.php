<section class="admin-panel service-image-manager">
    <div class="admin-panel-head">
        <div><p class="admin-step">معرض الخدمة</p><h2>صور {{ $service->name }}</h2><small>التحسين يتم عند الرفع عبر Queue، وليس أثناء زيارة صفحات الموقع.</small></div>
        <div class="preview-switch" role="group" aria-label="وضع المعاينة">
            <button type="button" wire:click="$set('previewMode','desktop')" @class(['active'=>$previewMode==='desktop'])>Desktop</button>
            <button type="button" wire:click="$set('previewMode','mobile')" @class(['active'=>$previewMode==='mobile'])>Mobile</button>
        </div>
    </div>

    @if(session('gallery_success'))<div class="notice-success" role="status">{{ session('gallery_success') }}</div>@endif

    <form wire:submit="uploadImages" class="service-image-upload">
        <label class="service-image-dropzone">
            <strong>اسحب عدة صور هنا أو اضغط للاختيار</strong>
            <small>حتى 30 صورة في الدفعة، 15MB للصورة. تُحفظ النسخة الأصلية خاصة ثم تعالج عبر Queue.</small>
            <input type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp,image/avif">
        </label>
        @error('uploads')<small class="field-error">{{ $message }}</small>@enderror
        @error('uploads.*')<small class="field-error">{{ $message }}</small>@enderror
        <button class="button button-primary" type="submit" wire:loading.attr="disabled">رفع وبدء المعالجة</button>
        <span wire:loading wire:target="uploads,uploadImages">جارٍ تجهيز الصور…</span>
    </form>

    @if($images->isNotEmpty())
        <p class="field-help">اسحب البطاقة من مقبض «ترتيب» لتغيير موضعها. الصورة ذات الشارة الذهبية هي الغلاف.</p>
        <div @class(['service-image-admin-grid','is-mobile-preview'=>$previewMode==='mobile']) wire:sort="updateOrder">
            @foreach($images as $image)
                @php($preview=$image->variant('thumbnail') ?: $image->variant('gallery'))
                <article wire:key="service-image-{{ $image->id }}" wire:sort:item="{{ $image->id }}" @class(['service-image-admin-card','is-cover'=>$image->is_cover,'is-degraded'=>$image->quality_status==='weak'])>
                    <div class="service-image-card-head"><button type="button" class="sort-handle" wire:sort:handle aria-label="اسحب لترتيب الصورة">↕ ترتيب</button>@if($image->is_cover)<span>صورة الغلاف</span>@endif</div>
                    <div class="service-image-preview">
                        @if($preview)<img src="{{ asset('storage/'.$preview['path']) }}" alt="{{ $image->alt_text }}" loading="lazy" width="{{ $preview['width'] }}" height="{{ $preview['height'] }}">@else<div class="processing-placeholder">{{ $image->processing_status === 'failed' ? 'فشلت المعالجة' : 'بانتظار المعالجة' }}</div>@endif
                    </div>
                    <dl class="image-quality-summary">
                        <div><dt>الحالة</dt><dd>{{ match($image->processing_status){'processed'=>'جاهزة','queued'=>'في Queue','failed'=>'فشلت',default=>'قيد المعالجة'} }}</dd></div>
                        <div><dt>الجودة</dt><dd>{{ $image->quality_label }} @if($image->quality_score)({{ $image->quality_score }}/100)@endif</dd></div>
                        <div><dt>الأصل</dt><dd dir="ltr">{{ $image->original_width }}×{{ $image->original_height }}</dd></div>
                    </dl>
                    <label><span>العنوان</span><input type="text" wire:model="metadata.{{ $image->id }}.title" maxlength="255"></label>
                    <label><span>Alt يصف ما يظهر</span><input type="text" wire:model="metadata.{{ $image->id }}.alt_text" maxlength="255"></label>
                    <label><span>Caption</span><textarea wire:model="metadata.{{ $image->id }}.caption" rows="2" maxlength="1000"></textarea></label>
                    <details class="processing-notes"><summary>ملاحظات المعالجة</summary><p>{{ $image->processing_notes ?: 'لا توجد ملاحظات بعد.' }}</p><small>المصدر: {{ $image->source_folder ?: 'رفع يدوي' }} · {{ $image->original_name }}</small></details>
                    <div class="service-image-actions">
                        <button type="button" wire:click="saveMetadata({{ $image->id }})">حفظ الوصف</button>
                        <button type="button" wire:click="setCover({{ $image->id }})" @disabled($image->processing_status!=='processed')>{{ $image->is_cover ? 'الغلاف الحالي' : 'اختيار كغلاف' }}</button>
                        <button type="button" wire:click="reprocess({{ $image->id }})">إعادة المعالجة</button>
                        <button type="button" class="danger" wire:click="deleteImage({{ $image->id }})" wire:confirm="نقل الصورة إلى المحذوفات؟">حذف قابل للاسترجاع</button>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="empty-state"><p>لا توجد صور خدمة بعد. يمكن رفعها هنا أو استيراد assets.zip بالأمر المخصص.</p></div>
    @endif

    @if($deletedImages->isNotEmpty())
        <details class="deleted-service-images"><summary>المحذوفات ({{ $deletedImages->count() }})</summary><div>@foreach($deletedImages as $image)<article><span>{{ $image->title ?: $image->original_name }}</span><button type="button" wire:click="restoreImage({{ $image->id }})">استعادة</button></article>@endforeach</div></details>
    @endif
</section>
