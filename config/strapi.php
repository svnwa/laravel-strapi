<?php

return [
    // '/api' is added automatically
    'apiToken' => env('STRAPI_API_TOKEN'),

    // Cache time in seconds, default 10 Minutes
    'cacheStorageTime' => env('STRAPI_CACHE_STORAGE_TIME',3600),

    'baseUrl' => env('STRAPI_BASE_URL'),

    'cacheResetRoute' => false,
];
