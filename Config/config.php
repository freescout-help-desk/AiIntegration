<?php

return [
    'name' => 'AiIntegration',
    'enabled' => env('AIINTEGRATION_ENABLED', true),
    'base_prompt' => '',
    'provider' => env('AIINTEGRATION_PROVIDER', 'openai'),
    'api_key' => env('AIINTEGRATION_API_KEY', ''),
    'base_url' => env('AIINTEGRATION_BASE_URL', ''),
    'model' => env('AIINTEGRATION_MODEL', ''),
    'send_images' => env('AIINTEGRATION_SEND_IMAGES', false),
    'include_notes' => env('AIINTEGRATION_INCLUDE_NOTES', false),
    'max_tokens' => env('AIINTEGRATION_MAX_TOKENS', ''),
    'auto_draft' => env('AIINTEGRATION_AUTO_DRAFT', true),
];
