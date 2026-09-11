@php
    $theme = app(\App\Support\ThemePalette::class);
    $preview = $theme->fromSettings($values);
@endphp
<form wire:submit="save" class="settings-editor">
    @foreach($groups as $group => $settings)
        @if($group === 'appearance')
            <section class="admin-panel appearance-panel" id="appearance">
                <div class="admin-panel-head">
                    <div>
                        <p class="admin-step">المظهر والألوان</p>
                        <h2>هوية الموقع</h2>
                        <small>تظهر المعاينة فورًا. لن يُحفظ أي لون لا يحقق صيغة HEX أو تباين WCAG المطلوب.</small>
                    </div>
                    <button class="button button-outline button-small" type="button" wire:click="resetAppearance">استعادة الافتراضي</button>
                </div>
                <div class="appearance-layout">
                    <div class="color-editor-grid">
                        @foreach($settings as $setting)
                            <label class="color-field">
                                <span>{{ $setting->label }}</span>
                                <span class="color-input-row">
                                    <input type="color" wire:model.live="values.{{ $setting->key }}" aria-label="اختيار {{ $setting->label }}">
                                    <input type="text" wire:model.live.debounce.120ms="values.{{ $setting->key }}" dir="ltr" maxlength="7" spellcheck="false" aria-label="قيمة HEX لـ {{ $setting->label }}">
                                </span>
                                @error('values.'.$setting->key)<small class="field-error-inline">{{ $message }}</small>@enderror
                            </label>
                        @endforeach
                    </div>
                    <div class="theme-live-preview" style="@foreach($preview as $name => $value){{ $name }}:{{ $value }};@endforeach">
                        <span class="preview-kicker">معاينة مباشرة</span>
                        <div class="preview-logo"><x-site-logo class="preview-brand-logo" :alt="$siteSettings['site_name']" /></div>
                        <h3>نصمم الظل كجزء من المكان</h3>
                        <p>هذه المعاينة تستخدم المتغيرات نفسها التي ستعمل في الموقع.</p>
                        <div><button type="button">ابدأ مشروعك</button><a href="#appearance">رابط ثانوي</a></div>
                        <article><strong>بطاقة خدمة</strong><small>نص ثانوي وحدود من الهوية المختارة.</small></article>
                    </div>
                </div>
            </section>
        @else
            <section class="admin-panel">
                <div class="admin-panel-head"><div><h2>{{ $group === 'business' ? 'بيانات النشاط' : ($group === 'seo' ? 'Search Console وGoogle Analytics' : $group) }}</h2>@if($group === 'seo')<small>ألصق المعرّفات فقط؛ لا تضف أكواد JavaScript.</small>@endif</div></div>
                <div class="admin-form-grid">
                    @foreach($settings as $setting)
                        <label><span>{{ $setting->label }} <small>{{ $setting->key }}</small></span><input type="text" wire:model="values.{{ $setting->key }}" @if(str_contains($setting->key, 'phone') || $group === 'seo') dir="ltr" @endif>@error('values.'.$setting->key)<small>{{ $message }}</small>@enderror</label>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
    <div class="admin-save-bar"><button class="button button-primary" type="submit">حفظ جميع الإعدادات</button><small>يمسح النظام ذاكرة الإعدادات المؤقتة فور الحفظ.</small></div>
</form>
