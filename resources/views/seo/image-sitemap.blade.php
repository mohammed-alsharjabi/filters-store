<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($items->groupBy('page') as $page => $images)
<url><loc>{{ $page }}</loc>@foreach($images as $item)<image:image><image:loc>{{ $item['image'] }}</image:loc><image:title>{{ $item['title'] }}</image:title>@if($item['caption'])<image:caption>{{ $item['caption'] }}</image:caption>@endif</image:image>@endforeach</url>
@endforeach
</urlset>
