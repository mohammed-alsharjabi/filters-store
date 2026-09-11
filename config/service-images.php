<?php

return [
    'source_zip' => env('SERVICE_IMAGES_ZIP', storage_path('app/assets.zip')),
    'maximum_entries' => 1000,
    'maximum_entry_bytes' => 25 * 1024 * 1024,
    'maximum_archive_bytes' => 1024 * 1024 * 1024,
    'maximum_dimension' => 12000,
    'maximum_pixels' => 80_000_000,
    'webp_quality' => 82,
    'avif_quality' => 58,
    'jpeg_quality' => 84,

    'curated_source' => env('SERVICE_IMAGES_DIRECTORY', storage_path('app/assets')),
    'auto_sync_on_web_request' => env('SERVICE_IMAGES_AUTO_SYNC', false),
    'additional_folders' => [],
    'curated_folders' => [],
    'visual_contexts' => [],
    'visual_overrides' => [],
    'retired_services' => [],

    'folders' => [
        ['contains' => ['installation', 'تركيب'], 'service' => 'تركيب فلاتر المياه بالرياض', 'stem' => 'water-filter-installation-riyadh', 'context' => 'تركيب فلتر مياه في الرياض'],
        ['contains' => ['replacement', 'شمعات'], 'service' => 'تغيير شمعات فلاتر المياه بالرياض', 'stem' => 'water-filter-replacement-riyadh', 'context' => 'تغيير شمعات فلتر المياه'],
        ['contains' => ['jumbo', 'جامبو'], 'service' => 'تركيب فلتر جامبو للخزان بالرياض', 'stem' => 'jumbo-water-filter-riyadh', 'context' => 'فلتر جامبو ثلاث مراحل'],
        ['contains' => ['shower', 'شاور'], 'service' => 'تركيب فلتر شاور بالرياض', 'stem' => 'shower-water-filter-riyadh', 'context' => 'فلتر مياه للشاور'],
        ['contains' => ['mist', 'fog', 'رذاذ', 'ضباب'], 'service' => 'تركيب وصيانة أنظمة الضباب والرذاذ بالرياض', 'stem' => 'mist-fog-system-riyadh', 'context' => 'نظام ضباب ورذاذ'],
        ['contains' => ['station', 'محطة'], 'service' => 'تركيب محطات تحلية المياه بالرياض', 'stem' => 'water-treatment-station-riyadh', 'context' => 'محطة تحلية مياه'],
        ['contains' => ['seven-stage', '7-stage', 'سبع'], 'service' => 'تركيب أجهزة تحلية المياه المنزلية بالرياض', 'stem' => 'seven-stage-water-purifier-riyadh', 'context' => 'جهاز تحلية مياه منزلي سبع مراحل'],
    ],

    'service_stems' => [
        'تركيب فلاتر المياه بالرياض' => 'water-filter-installation-riyadh',
        'صيانة فلاتر المياه بالرياض' => 'water-filter-maintenance-riyadh',
        'تغيير شمعات فلاتر المياه بالرياض' => 'water-filter-replacement-riyadh',
        'تركيب أجهزة تحلية المياه المنزلية بالرياض' => 'home-water-purifier-installation-riyadh',
        'صيانة أجهزة تحلية المياه بالرياض' => 'home-water-purifier-maintenance-riyadh',
        'تركيب محطات تحلية المياه بالرياض' => 'water-treatment-station-installation-riyadh',
        'صيانة محطات تحلية المياه بالرياض' => 'water-treatment-station-maintenance-riyadh',
        'تركيب فلتر جامبو للخزان بالرياض' => 'jumbo-water-filter-riyadh',
        'تركيب فلتر شاور بالرياض' => 'shower-water-filter-riyadh',
        'تركيب وصيانة أنظمة الضباب والرذاذ بالرياض' => 'mist-fog-system-riyadh',
    ],
];
