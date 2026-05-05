<?php

return [
    'base_url' => env('WAHA_BASE_URL', 'http://waha:3000'),
    'api_key' => env('WAHA_API_KEY'),
    'session' => env('WAHA_SESSION', 'default'),
    'timeout' => (int) env('WAHA_TIMEOUT', 15),
    'contacts_page_size' => (int) env('WAHA_CONTACTS_PAGE_SIZE', 1000),
    'contacts_max_pages' => (int) env('WAHA_CONTACTS_MAX_PAGES', 20),
    'contacts_preview_cache_seconds' => (int) env('WAHA_CONTACTS_PREVIEW_CACHE_SECONDS', 300),
];
