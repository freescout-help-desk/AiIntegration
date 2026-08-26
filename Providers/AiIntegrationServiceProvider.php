<?php

namespace Modules\AiIntegration\Providers;

use Modules\AiIntegration\Misc\ApiCallException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('AII_MODULE', 'aiintegration');

class AiIntegrationServiceProvider extends ServiceProvider
{
    const LOG_NAME = 'ai_integration';

    public static $providers = [
        'openai' => [
            'name' => 'OpenAI',
            'base_url' => 'https://api.openai.com/v1',
            'requires_api_key' => true,
            'embedding_model' => 'text-embedding-3-small',
        ],
        'gemini' => [
            'name' => 'Gemini (Google AI Studio)',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'requires_api_key' => true,
            //'models_endpoint' => '/v1beta/models',
            'embedding_model' => 'gemini-embedding-001',
            // First - dot separated path: data.models
            'models_names_in_response' => ['models' => 'name'],
        ],
        /*'anthropic' => [
            'name' => 'Anthropic (Claude)',
            'base_url' => 'https://api.anthropic.com/v1',
            'requires_api_key' => true,
            'openai_compatible' => false,
        ],*/
        'deepseek' => [
            'name' => 'DeepSeek',
            'base_url' => 'https://api.deepseek.com',
            'requires_api_key' => true,
            'models_names_in_response' => ['data' => 'id'],
        ],
        'xai' => [
            'name' => 'xAI',
            'base_url' => 'https://api.x.ai/v1',
            'requires_api_key' => true,
        ],
        'mistral' => [
            'name' => 'Mistral AI',
            'base_url' => 'https://api.mistral.ai/v1',
            'requires_api_key' => true,
            'embedding_model' => 'mistral-embed',
        ],
        'groq' => [
            'name' => 'Groq',
            'base_url' => 'https://api.groq.com/openai/v1',
            'requires_api_key' => true,
        ],
        'perplexity' => [
            'name' => 'Perplexity',
            'base_url' => 'https://api.perplexity.ai',
            'requires_api_key' => true,
        ],
        'together' => [
            'name' => 'Together AI',
            'base_url' => 'https://api.together.xyz/v1',
            'requires_api_key' => true,
            'embedding_model' => 'BAAI/bge-large-en-v1.5',
        ],
        'fireworks' => [
            'name' => 'Fireworks AI',
            'base_url' => 'https://api.fireworks.ai/inference/v1',
            'requires_api_key' => true,
            'embedding_model' => 'fireworks/qwen3-embedding-8b',
        ],
        'digitalocean' => [
            'name' => 'DigitalOcean Serverless Inference',
            'base_url' => 'https://inference.do-ai.run/v1',
            'requires_api_key' => true,
            'embedding_model' => 'qwen3-embedding-0.6b',
        ],
        'zai' => [
            'name' => 'Z.AI',
            'base_url' => 'https://api.z.ai/api/paas/v4',
            'requires_api_key' => true,
        ],
        'ollama' => [
            'name' => 'Ollama (Local)',
            'base_url' => 'http://localhost:11434/v1',
            'requires_api_key' => false,
            'embedding_model' => 'nomic-embed-text',
        ],
        'lmstudio' => [
            'name' => 'LM Studio (Local)',
            'base_url' => 'http://localhost:1234/v1',
            'requires_api_key' => false,
            'embedding_model' => 'text-embedding-nomic-embed-text-v1.5',
        ],
        'openrouter' => [
            'name' => 'OpenRouter',
            'base_url' => 'https://openrouter.ai/api/v1',
            'requires_api_key' => true,
            'embedding_model' => 'openai/text-embedding-3-small',
        ],
        'custom' => [
            'name' => 'Custom (OpenAI-compatible)',
            'base_url' => '',
            'requires_api_key' => true,
            'embedding_model' => '',
        ],
    ];

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add functions to providers config.
        self::extendProvidersConfig();

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(AII_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(AII_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['ai-integration'] = ['title' => __('AI Integration'), 'icon' => 'cloud', 'order' => 250];

            return $sections;
        }, 40);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != 'ai-integration') {
                return $settings;
            }
           
            $settings = self::getSettings();

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'ai-integration') {
                return $params;
            }

            $params = [
                'settings' => [
                    'aiintegration.provider' => [
                        'env' => 'AIINTEGRATION_PROVIDER',
                    ],
                    'aiintegration.api_key' => [
                        'env' => 'AIINTEGRATION_API_KEY',
                        'encrypt' => true,
                    ],
                    'aiintegration.base_url' => [
                        'env' => 'AIINTEGRATION_BASE_URL',
                    ],
                    'aiintegration.model' => [
                        'env' => 'AIINTEGRATION_MODEL',
                    ],
                ],
            ];

            return $params;
        }, 20, 2);

        // Settings view name.
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'ai-integration') {
                return $view;
            } else {
                return 'aiintegration::settings';
            }
        }, 20, 2);

        // Before saving settings
        \Eventy::addFilter('settings.before_save', function($request, $section, $settings) {
            if ($section != 'ai-integration') {
                return $request;
            }

            $settings = $request->settings ?? [];
            
            if (!empty($settings['aiintegration.base_url'])) {

                $settings['aiintegration.base_url'] = preg_replace("/https?:\/\//i", '', $settings['aiintegration.base_url']);

                try {
                    if (!\Helper::sanitizeRemoteUrl('https://'.$settings['aiintegration.base_url'], true)) {
                        $settings['aiintegration.base_url'] = '';
                    }
                } catch (\Exception $e) {
                    $request->session()->flash('flash_error', $e->getMessage());
                    $settings['aiintegration.base_url'] = '';
                }
            }

            // Do not save dummy value.
            if (\Helper::isSafePassword($settings['aiintegration.api_key'])) {
                // Get prev value.
                $settings['aiintegration.api_key'] = self::getSetting('api_key');
            }

            $request->merge([
                'settings' => $settings
            ]);

            return $request;
        }, 20, 3);
    }

    // Add functions to providers config.
    public static function extendProvidersConfig()
    {
        $providers_extended = [
            'gemini' => [
                'get_models_base_url_fn' => function($base_url, $api_key) {
                    return preg_replace("#/openai/?$#", '/models', $base_url).'?key='.$api_key;
                },
            ],
        ];
        foreach ($providers_extended as $provider_name => $provider_config) {
            self::$providers[$provider_name] = array_merge(self::$providers[$provider_name], $provider_config);
        }
    }

    public static function getSettings()
    {
        $settings = [];

        $fields = [
            'provider',
            'api_key',
            'base_url',
            'model',
        ];

        foreach ($fields as $field) {
            $settings['aiintegration.'.$field] = self::getSetting($field);
        }

        return $settings;
    }

    public static function getSetting($key)
    {
        $value = config('aiintegration.'.$key);

        if (in_array($key, ['api_key'])) {
            return \Helper::decrypt($value);
        } else {
            return $value;
        }
    }

    public static function isEnabled()
    {
        return false;
    }

    public static function getProviders()
    {
        return self::$providers;
    }

    public static function getProviderConfig($param = '', $provider = '')
    {
        if (!$provider) {
            $provider = self::getSetting('provider');
        }
        if ($param) {
            return self::$providers[$provider][$param] ?? null;
        } else {
            return self::$providers[$provider] ?? [];
        }
    }

    public static function apiGetModels($settings)
    {
        try {
            $response = self::apiRequest('/models', [], $settings);
        } catch (ApiCallException $e) {
            return [
                'status' => 'error',
                'msg' => $e->getMessage()
            ];
        }
        $models = [];

        if (!empty($response)) {
            $models_names_in_response = self::getProviderConfig('models_names_in_response') ?: ['models' => 'name'];

            $first_key = array_key_first($models_names_in_response);
            $list = array_get($response, $first_key);
            $models = array_column($list, $models_names_in_response[$first_key]);

            // Remove everything before "/" in model name.
            $models = array_map(function($model) {
                return preg_replace("#.*/#", '', $model);
            }, $models);
        }

        return [
            'status' => 'success',
            'models' => $models,
        ];
    }

    public static function apiChatCompletions($content, $json_schema, $max_tokens = 1000)
    {
        $data = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
            'max_tokens' => $max_tokens,
            'model' => $model,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => $json_schema,
            ]
        ];

        try {
            $response = self::apiRequest('/chat/completions', $data);
        } catch (ApiCallException $e) {
            self::logApiEexception($e);
            return [
                'status' => 'error',
                'msg' => $e->getMessage()
            ];
        }

        return [
            'status' => 'success',
            'data' => $response['choices'][0]['message']['content'] ?? '',
        ];
    }

    // If $data is passed, POST is used.
    private static function apiRequest($method, $data = [], $settings = [], $http_method = 'POST')
    {
        $provider = $settings['provider'] ?: self::getSetting('provider');
        $api_key = (!\Helper::isSafePassword($settings['api_key']) ? $settings['api_key'] : '') ?: self::getSetting('api_key');
        $base_url = $settings['base_url'] ?: self::getSetting('base_url') ?: self::getProviderConfig('base_url', $provider) ?? '';

        $requires_api_key = self::getProviderConfig('requires_api_key', $provider) ?? false;

        if (!$api_key && $requires_api_key) {
            throw new \ApiCallException('API Key is required');
        }

        $url = $base_url.$method;
        $get_models_base_url_fn = null;
        if ($method == '/models') {
            // Some providers have their own way of retrieving /models.
            $get_models_base_url_fn = self::getProviderConfig('get_models_base_url_fn', $provider);
            if ($get_models_base_url_fn) {
                $url = $get_models_base_url_fn($base_url, $api_key);
            }
        }

        $json_data = json_encode($data);
        $headers = [
            'Content-Type: application/json',
        ];
        if (!$data) {
            $http_method = 'GET';
        }
        if ($http_method == 'POST') {
            $headers[] = 'Content-Length: ' . strlen($json_data);
        }

        if ($api_key && !$get_models_base_url_fn) {
            $headers[] = 'Authorization: Bearer ' . $api_key;
        }

        $response = [];
        $max_attempts = 3;
        $retry_delay_ms = 500;

        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                //CURLOPT_USERAGENT => 'FreeScout-AI-Integration/1.0'
            ]);
            if ($http_method == 'POST') {
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $json_data,
                ]);
            }
            \Helper::setCurlDefaultOptions($ch);

            $response = curl_exec($ch);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //$total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $error = curl_error($ch);
            $errno = curl_errno($ch);

            \Helper::curlClose($ch);

            if ($error) {
                // Curl error.
                if (in_array($errno, [
                        CURLE_OPERATION_TIMEDOUT,
                        CURLE_COULDNT_CONNECT,
                        CURLE_COULDNT_RESOLVE_HOST,
                        CURLE_COULDNT_RESOLVE_PROXY,
                        CURLE_GOT_NOTHING,
                        CURLE_RECV_ERROR,
                        CURLE_SEND_ERROR,
                    ])
                ) {
                    // Retry.
                    usleep($retry_delay_ms * $attempt * 1000);
                    continue;
                } else {
                    throw new ApiCallException('Curl Error: '.$error.' ('.$errno.'). URL: '.$url);
                }
            } elseif ($http_code < 200 || $http_code >= 300) {
                // HTTP error.
                if (in_array($http_code, [408, 425, 429, 500, 502, 503, 504])) {
                    // Retry.
                    usleep($retry_delay_ms * $attempt * 1000);
                    continue;
                } elseif ($attempt == $max_attempts) {
                    throw new ApiCallException('HTTP Error: '.$http_code. '. URL: '.$url.'. Response: '.$response);
                }
            } elseif ($response) {
                // Success
                break;
            }
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \ApiCallException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    public static function logApiEexception($e)
    {
        if (\Helper::isConsole()) {
            \Log::error('[AI Integration] '.$e->getMessage());
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('aiintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'aiintegration'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/aiintegration');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/aiintegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'aiintegration');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
