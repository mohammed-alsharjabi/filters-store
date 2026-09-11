@props(['eyebrow'=>null,'title','description'=>null,'align'=>'right'])
<div @class(['section-heading','text-center'=>$align==='center','mx-auto'=>$align==='center'])>
    @if($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
    <h2>{{ $title }}</h2>
    @if($description)<p>{{ $description }}</p>@endif
</div>
