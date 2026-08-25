<?php

return [
    'name' => 'AiIntegration',
    'provider' => env('AIINTEGRATION_PROVIDER', 'openai'),
    'api_key' => env('AIINTEGRATION_API_KEY', ''),
    'base_url' => env('AIINTEGRATION_BASE_URL', ''),
    'model' => env('AIINTEGRATION_MODEL', ''),
];
