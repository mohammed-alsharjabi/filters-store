@props(['faqs'])
@if($faqs->isNotEmpty())
<section class="faq-section section-indexed">
    <div class="container-narrow"><x-section-heading eyebrow="إجابات واضحة" title="أسئلة شائعة" />
        <div class="faq-list mt-9">@foreach($faqs as $faq)<details><summary>{{ $faq->question }}<span aria-hidden="true">+</span></summary><div><p>{{ $faq->answer }}</p></div></details>@endforeach</div>
    </div>
</section>
@endif
