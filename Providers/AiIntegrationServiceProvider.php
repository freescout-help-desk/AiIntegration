<?php

namespace Modules\AiIntegration\Providers;

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
            'name' => 'Google AI Studio (Gemini)',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'requires_api_key' => true,
            'embedding_model' => 'gemini-embedding-001',
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
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            //$javascripts[] = \Module::getPublicPath(AII_MODULE).'/js/laroute.js';
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
                        'env' => 'AIINTEGRATION_BASE_URL',
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

            $new_settings = $request->settings ?? [];
            
            //$new_settings['test'] = $custom_statuses;

            $request->merge(['settings' => array_merge($request->settings ?? [], $new_settings)]);

            return $request;
        }, 20, 3);
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

    public static function getProvider()
    {
        return self::$providers[self::getSetting('provider')] ?? [];
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
