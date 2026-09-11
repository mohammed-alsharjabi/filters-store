<div>
    <div class="admin-filters">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="بحث بالاسم أو الجوال">
        <select wire:model.live="status">
            <option value="">كل الحالات</option>
            @foreach($statusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <section class="admin-panel">
        <div class="admin-table-wrap">
            <table>
                <thead><tr><th>العميل</th><th>التواصل</th><th>تفاصيل الطلب</th><th>المصدر والمرفقات</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td><strong>{{ $lead->name ?: 'عميل بدون اسم' }}</strong><small>{{ $lead->area ?: 'منطقة غير محددة' }}</small><small>{{ $lead->area_size ? $lead->area_size.' م² تقريبًا' : 'المساحة غير محددة' }}</small></td>
                            <td>
                                <a href="tel:{{ $lead->phone }}" dir="ltr">{{ $lead->phone }}</a>
                                <small>{{ $lead->preferred_contact === 'whatsapp' ? 'يفضل واتساب' : 'يفضل الاتصال' }}</small>
                                @if($lead->whatsapp_message)<a class="admin-whatsapp" href="https://wa.me/{{ preg_replace('/\D+/', '', $lead->phone) }}?text={{ rawurlencode($lead->whatsapp_message) }}" target="_blank" rel="noopener">فتح واتساب برسالة جاهزة</a>@endif
                            </td>
                            <td><strong>{{ $lead->service?->name ?: 'خدمة غير محددة' }}</strong><small>{{ $lead->message ?: 'لا توجد تفاصيل إضافية.' }}</small></td>
                            <td>
                                @if($lead->source_url)<a href="{{ $lead->source_url }}" target="_blank" rel="noopener">صفحة الوصول</a>@else<small>المصدر غير مسجل</small>@endif
                                <small>{{ $lead->created_at->diffForHumans() }}</small>
                                @if($lead->images->isNotEmpty())
                                    <div class="lead-files">@foreach($lead->images as $index => $image)<a href="{{ route('admin.leads.images.download', $image) }}">صورة {{ $index + 1 }}</a>@endforeach</div>
                                @endif
                            </td>
                            <td><select wire:change="updateStatus({{ $lead->id }}, $event.target.value)">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>@endforeach</select></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">لا توجد نتائج.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-6">{{ $leads->links() }}</div>
</div>
