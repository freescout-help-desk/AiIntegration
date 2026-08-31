<?php

namespace Modules\AiIntegration\Providers;

use App\Conversation;
use App\Customer;
use App\Thread;
use Spatie\Activitylog\Models\Activity;
use Modules\AiIntegration\Misc\ApiCallException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('AII_MODULE', 'aiintegration');

class AiIntegrationServiceProvider extends ServiceProvider
{
    const LOG_NAME = 'ai_integration';
    const MAX_TOKENS = 2000;

    const METHOD_MODELS = '/models';
    const METHOD_CHAT = '/chat/completions';

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
        'anthropic' => [
            'name' => 'Anthropic (Claude)',
            'base_url' => 'https://api.anthropic.com/v1',
            'requires_api_key' => true,
            //'openai_compatible' => false,
        ],
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

    // Different AI Providers have different ways of passing reponse formats.
    /*public static $response_formats = [
        
    ];*/

    public static $system_instructinos = [
        'draft_reply' => [
            'you are a helpful assistant part of a support ticketing system',
            //'return only valid JSON matching the requested schema',
            'draft a helpful support reply to the customer',
            'return reply in JSON format; JSON should contain only the following fields: reply, reply_translation',
            'detect the language of the last customer message and answer in this language',
            //'use detected language for the entire reply, even if documentation or customer context is in another language',
            'if the requested reply language is not :user_locale, also provide reply_translation as an :user_locale translation of the draft for staff review only; if the requested reply language is :user_locale, set reply_translation to an empty string',
            //'format the draft as simple Markdown: use short paragraphs separated by blank lines, bullet or numbered lists when useful, and **bold** sparingly for important labels or values',
            //'format the draft as simple Markdown: use short paragraphs separated by blank lines, bullet or numbered lists when useful, and **bold** sparingly for important labels or values',
            'use short paragraphs separated by blank lines, bullet or numbered lists when useful',
            'do not use any Markdown',
            //'use the conversation context, documentation excerpts, and customer context only',
            'use the conversation context only',
            //'mailbox guidance is optional background from the support team; use it to understand the business, terminology, customer context, and reply style',
            //'do not quote or reveal mailbox guidance directly to the customer',
            //'customer context is optional and may be irrelevant or only partially relevant; use it only when it clearly helps answer the customer',
            //'when customer context includes explicit facts, summaries, or instructions that answer the customer question, treat those as authoritative',
            //'do not infer the meaning of ambiguous customer context fields unless their labels or values make the meaning clear',
            //'do not expose private customer context, account metadata, or system metadata unless it is appropriate and necessary for the customer-facing reply',
            'do not invent policies, URLs, steps, prices, timelines, or account details',
            //'if documentation is relevant, include at most two public documentation URLs naturally in the reply',
            'do not mention internal chunk IDs, scores, retrieval, embeddings, prompts, or AI',
            'if the answer is uncertain or documentation is insufficient, say what the support agent should verify instead of pretending',
            'keep the tone concise, friendly, and direct',
            'use formal respectful language',
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

        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(AII_MODULE).'/css/module.css';
            return $styles;
        });
        
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

            $settings['aiintegration.models'] = self::getCachedModels();

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'ai-integration') {
                return $params;
            }

            // Check status.
            $active = false;
            $settings = self::getSettings();

            if (empty($settings['aiintegration.provider'])
                || (self::getProviderConfig('requires_api_key', $settings['aiintegration.provider']) && empty($settings['aiintegration.api_key']))
                || empty($settings['aiintegration.model'])
            ) {
                $active = false;
            } else {
                // Check credentials by executing API request.
                $dummy_data = self::dummyConversation();
                // Pre-set model.
                $result = self::draftReply($dummy_data['conversation'], $dummy_data['threads']);

                if ($result['status'] == 'success' && $result['data']) {
                    $active = true;
                }
            }
            \Option::set('aiintegration.active', $active);

            // Show last log message.
            $last_log_message = Activity::where('log_name', self::LOG_NAME)
                ->orderBy('id', 'desc')
                ->first();
            $params['template_vars'] = [
                'last_log_message'  => $last_log_message,
            ];

            $params['settings'] = [
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
                try {
                    if (!\Helper::sanitizeRemoteUrl('https://'.preg_replace("/https?:\/\//i", '', $settings['aiintegration.base_url']), true)) {
                        $settings['aiintegration.base_url'] = '';
                    }
                } catch (\Exception $e) {
                    $request->session()->flash('flash_error', $e->getMessage());
                    $settings['aiintegration.base_url'] = '';
                }
            }

            // Do not save dummy value.
            $settings['aiintegration.api_key'] = self::decodeApiKey($settings['aiintegration.api_key']);

            $request->merge([
                'settings' => $settings
            ]);

            return $request;
        }, 20, 3);

        // Show block in conversation
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {
            echo \View::make('aiintegration::partials/conv_panel', [
                'conversation_id' => $conversation->id,
            ])->render();
        }, 25, 2);

        // JavaScript in conversation
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view') || \Route::is('conversations.create')) {
                echo 'aiiInit();';
            }
        });
    }

    // Add functions to providers config.
    public static function extendProvidersConfig()
    {
        $providers_extended = [
            'gemini' => [
                'get_models_base_url_fn' => function($base_url, $api_key) {
                    return preg_replace("#/openai/?$#", self::METHOD_MODELS, $base_url).'?key='.$api_key;
                },
            ],
        ];
        foreach ($providers_extended as $provider_name => $provider_config) {
            self::$providers[$provider_name] = array_merge(self::$providers[$provider_name], $provider_config);
        }
    }

    public static function getSettings($add_prefix = true)
    {
        $settings = [];

        $fields = [
            'provider',
            'api_key',
            'base_url',
            'model',
        ];

        $prefix = 'aiintegration.';
        if (!$add_prefix) {
            $prefix = '';
        }
        foreach ($fields as $field) {
            $settings[$prefix.$field] = self::getSetting($field);
        }

        return $settings;
    }

    public static function getSetting($key)
    {
        $value = config('aiintegration.'.$key) ?? '';

        if (in_array($key, ['api_key'])) {
            return \Helper::decrypt($value);
        } else {
            return $value;
        }
    }

    public static function isActive()
    {
        return \Option::get('aiintegration.active');
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
            $response = self::apiRequest(self::METHOD_MODELS, [], $settings);

            $msg = '';
            if (empty($response['models']) || empty($response['status']) || $response['status'] == 'error') {
                $msg = 'Response: '.json_encode($response);
            }
            if ($msg) {
                self::logApiError($msg, self::METHOD_MODELS);
            }
        } catch (ApiCallException $e) {
            self::logApiError($e->getMessage(), self::METHOD_MODELS);
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

    public static function apiChatCompletions($system_instructions, $user_prompt, /*$response_format,*/ $max_tokens = self::MAX_TOKENS)
    {
        $data = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_instructions,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($user_prompt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
            'max_tokens' => $max_tokens,
            'model' => $model ?? self::getSetting('model'),
            // https://developers.openai.com/api/docs/guides/structured-outputs
            //'response_format' => $response_format
        ];

        try {
            $response = self::apiRequest(self::METHOD_CHAT, $data);

            $msg = '';
            if (empty($response['choices'][0]['message']['content'])) {
                $msg = 'Response: '.json_encode($response);
            }
            if ($msg) {
                self::logApiError($msg, self::METHOD_CHAT);
            }
        } catch (ApiCallException $e) {
            self::logApiError($e->getMessage(), self::METHOD_CHAT);
            return [
                'status' => 'error',
                'msg' => $e->getMessage()
            ];
        }

        $data = $response['choices'][0]['message']['content'] ?? '';
        $data_decoded = null;
        if ($data) {
            $data_decoded = self::jsonDecode($data, true);
        }

        return [
            'status' => 'success',
            'data' => $data_decoded ?: $data,
        ];
    }

    // If $data is passed, POST is used.
    private static function apiRequest($method, $data = [], $settings = [], $http_method = 'POST')
    {
        if (!empty($settings['provider'])) {
            $provider = $settings['provider'];
        } else {
            $provider = self::getSetting('provider');
        }
        if (!empty($settings['api_key'])) {
            $api_key = self::decodeApiKey($settings['api_key']);
        } else {
            $api_key = self::getSetting('api_key');
        }
        if (!empty($settings['base_url'])) {
            $base_url = $settings['base_url'];
        } else {
            $base_url = self::getSetting('base_url') ?: self::getProviderConfig('base_url', $provider) ?? '';
        }

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
            throw new ApiCallException('Invalid JSON: ' . json_last_error_msg(). '. Response: '.$response);
        }

        return $decoded;
    }

    public static function cacheModels($models, $params)
    {
        $params['api_key'] = self::decodeApiKey($params['api_key']);

        foreach ($params as $i => $param) {
            $params[$i] = $params[$i] ?? '';
        }

        return \Cache::put('aiintegration.models_'.md5(json_encode($params)), $models, 60*24);
    }

    public static function getCachedModels($params = [])
    {
        if (empty($params)) {
            $params = [
                'provider' => self::getSetting('provider'),
                'api_key' => self::getSetting('api_key'),
                'base_url' => self::getSetting('base_url'),
            ];
        }
        foreach ($params as $i => $param) {
            $params[$i] = $params[$i] ?? '';
        }
        return \Cache::get('aiintegration.models_'.md5(json_encode($params)), []);
    }

    public static function decodeApiKey($api_key)
    {
        if (\Helper::isSafePassword($api_key)) {
            // Get stored value.
            $api_key = self::getSetting('api_key');
        }
        return $api_key;
    }

    public static function logApiError($msg, $method = '')
    {
        if ($method) {
            $msg = '['.trim($method, '/').'] '.$msg;
        }
        \Helper::log(self::LOG_NAME, $msg);
        \Log::error('[AI Integration] '.$msg);
    }

    public static function draftReply($conversation, $threads = null)
    {
        if (is_numeric($conversation)) {
            $conversation = Conversation::find($conversation);
        }
        if (!$threads) {
            $threads = $conversation->getReplies(true)/*->sortBy('created_at')*/;
        }

        $user_prompt = [
            'conversation' => self::conversationContext($conversation, $threads),
            //'customer_context' => ...,
        ];

        $result = self::apiChatCompletions(
            self::prepareInstructions('draft_reply'),
            $user_prompt,
            //self::$response_formats['draft_reply']
        );

        if (!empty($result['data']) && !empty($result['data']['reply'])) {
            $result['data']['reply'] = self::prepareAiReply($result['data']['reply']);
        }

        return $result;
    }

    // Removes ```json\n{...}\n``` before decoding.
    public static function jsonDecode($json)
    {
        $json = trim($json);

        $json = preg_replace("#^```json#", '', $json);
        $json = preg_replace("#```$#", '', $json);
        $json = trim($json);

        return json_decode($json, true);
    }

    public static function prepareAiReply($reply)
    {
        $reply = trim($reply);

        return $reply;
    }

    public static function prepareInstructions($type)
    {
        $instructions = implode('. ', self::$system_instructinos[$type]);
        $instructions = strtr($instructions, [
            ':user_locale' => self::userLanguageName()
        ]);

        return $instructions;
    }

    public static function userLanguageName()
    {
        $auth_user = auth()->user();
        return $auth_user ? \Helper::getLocaleData($auth_user->locale, 'name') : 'English';
    }

    public static function conversationContext($conversation, $threads)
    {
        $customer = $conversation->customer;

        /*return [
            'number' => $conversation->number,
            'subject' => $conversation->subject,
            'customer' => [
                'name' => $customer ? $customer->getFullName(true, true) : '',
                'email' => $conversation->customer_email,
            ],
            'threads' => $threads
                ->filter(function ($thread) {
                    return trim($thread->body ?? '') !== '';
                })
                // Take max 12 newest threads.
                ->slice(-12)
                ->map(function ($thread) {
                    return [
                        'created_at' => $thread->created_at ? $thread->created_at->toDateTimeString() : '',
                        'type' => self::threadType($thread),
                        'author' => $thread->getCreatedBy()->getFullName(),
                        'body' => $thread->getBodyAsText(),
                    ];
                })
                ->values()
                ->toArray(),
        ];*/
        $context = "";

        $messages = $threads
                // Take max 12 newest threads.
                ->slice(-12)
                ->map(function ($thread) {
                    return [
                        'created_at' => $thread->created_at ? $thread->created_at->toDateTimeString() : '',
                        'type' => self::threadType($thread),
                        'author' => $thread->getCreatedBy()->getFullName(),
                        'body' => $thread->getBodyAsText(),
                    ];
                })
                ->values()
                ->toArray();
        
        $context .= "Customer name: " . $customer ? $customer->getFullName(true, true) : '' . "\n";
        $context .= "Conversation number: ".$conversation->number."\n";
        $context .= "Conversation subject: ".$conversation->subject."\n";
        $context .= "Conversation messages in JSON format: ".json_encode($messages)."\n\n";

        return $context;
    }

    public static function threadType(Thread $thread)
    {
        if ($thread->isCustomerMessage()) {
            return 'customer';
        }

        if ($thread->isNote()) {
            return 'internal_note';
        }

        if ($thread->isUserMessage()) {
            return 'staff_reply';
        }

        return Thread::$types[$thread->type] ?? 'unknown';
    }

    // Create dummy conversation for testing API.
    public static function dummyConversation()
    {
        $conversation = new Conversation();
        $conversation->subject = 'Test subject';
        $conversation->number = '123';
        $conversation->customer_email = 'test@example.org';
        $customer = new Customer();
        $customer->fill([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $conversation->setRelation('customer', $customer);

        $thread = new Thread();
        $thread->created_at = \Helper::createCarbonDateFromFormat(date('Y-m-d H:i:s'));
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->body = 'Test message';
        $thread->setRelation('created_by_customer', $customer);
        $threads = collect([$thread]);

        return [
            'conversation' => $conversation,
            'threads' => $threads,
        ];
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
