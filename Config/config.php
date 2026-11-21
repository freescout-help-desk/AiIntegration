<?php

return [
    'name' => 'AiIntegration',
    'enabled' => env('AIINTEGRATION_ENABLED', true),
    'base_prompt' => env('AIINTEGRATION_BASE_PROMPT', ''),
    'provider' => env('AIINTEGRATION_PROVIDER', 'openai'),
    'api_key' => env('AIINTEGRATION_API_KEY', ''),
    'base_url' => env('AIINTEGRATION_BASE_URL', ''),
    'model' => env('AIINTEGRATION_MODEL', ''),
    'send_images' => env('AIINTEGRATION_SEND_IMAGES', false),
    'include_notes' => env('AIINTEGRATION_INCLUDE_NOTES', false),
    'max_tokens' => env('AIINTEGRATION_MAX_TOKENS', ''),
    'auto_draft' => env('AIINTEGRATION_AUTO_DRAFT', true),
    // New tickets created by customers: set the Priority detected by AI.
    'priority_auto_set' => env('AIINTEGRATION_PRIORITY_AUTO_SET', false),
    // New tickets created by customers: run "AI Detected Priority" Condition in Workflows.
    'priority_workflow' => env('AIINTEGRATION_PRIORITY_WORKFLOW', false),
];
