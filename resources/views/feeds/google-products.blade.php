<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<title>{{ $siteSettings['site_name'] }}</title>
<link>{{ route('products.index') }}</link>
<description>منتجات فلاتر وتحلية المياه المتوفرة في الرياض</description>
@foreach($products as $product)
<item>
<g:id>{{ $product->sku ?: $product->id }}</g:id>
<g:title>{{ $product->name }}</g:title>
<g:description>{{ $product->excerpt ?: strip_tags((string) $product->description) }}</g:description>
<g:link>{{ route('products.show', $product->slug) }}</g:link>
<g:image_link>{{ $product->imageUrl() }}</g:image_link>
<g:availability>{{ $product->availabilityForFeed() }}</g:availability>
<g:price>{{ number_format((float) $product->price, 2, '.', '') }} SAR</g:price>
<g:condition>{{ $product->condition }}</g:condition>
<g:brand>{{ $product->brand }}</g:brand>
<g:product_type>{{ $product->category->name }}</g:product_type>
@if($product->gtin)<g:gtin>{{ $product->gtin }}</g:gtin>@endif
@if($product->mpn)<g:mpn>{{ $product->mpn }}</g:mpn>@endif
@if(!$product->gtin && !$product->mpn)<g:identifier_exists>no</g:identifier_exists>@endif
</item>
@endforeach
</channel>
</rss>
