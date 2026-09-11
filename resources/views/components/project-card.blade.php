@props(['project'])
<article class="content-card project-card">
    @php($cover=$project->images->firstWhere('is_cover',true) ?? $project->images->first())
    @if($cover)<a href="{{ route('projects.show',$project->slug) }}"><x-responsive-image :image="$cover" :alt="$cover->alt_text ?: $project->title" sizes="(max-width: 700px) 100vw, (max-width: 1024px) 50vw, 33vw" /></a>@else<div class="project-placeholder" aria-hidden="true"><span>مشروع موثق</span></div>@endif
    <div class="card-body"><p class="card-kicker">{{ $project->service->name }} · {{ $project->area->name }}</p><h3><a href="{{ route('projects.show',$project->slug) }}">{{ $project->title }}</a></h3><p>{{ $project->excerpt }}</p></div>
</article>
