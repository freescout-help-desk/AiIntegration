<?php

// Standalone regression tests: php Tests/prompts.php (no API calls).
namespace Illuminate\Support {
    class ServiceProvider {}
}
namespace Illuminate\Routing {
    class Controller {}
}

namespace {
    require __DIR__.'/../Providers/AiIntegrationServiceProvider.php';
    require __DIR__.'/../Http/Controllers/AiIntegrationController.php';

    use Modules\AiIntegration\Providers\AiIntegrationServiceProvider as AI;

    class Option
    {
        public static $values = [];
        public static function get($key, $default = false) { return self::$values[$key] ?? $default; }
    }
    class Helper
    {
        public static function getAppLocale() { return 'en'; }
        public static function getLocaleData($locale, $field) { return 'English'; }
    }
    class TestUser
    {
        public $id = 7;
        public $locale = 'en';
        public $admin = false;
        public $allowed = false;
        public function isAdmin() { return $this->admin; }
        public function hasPermission($permission) { return $permission === AI::PERM_EDIT_PROMPT && $this->allowed; }
    }
    class TestMailbox
    {
        public $settings = [];
        public function getMeta($key, $default) { return $this->settings; }
    }
    $settings = ['aiintegration.enabled' => true];
    $actor = new TestUser();
    function config($key) { return $GLOBALS['settings'][$key] ?? ''; }
    function auth() { return new class { public function user() { return $GLOBALS['actor']; } }; }
    function abort_unless($allowed, $code) { if (!$allowed) { throw new \RuntimeException('HTTP '.$code); } }
    class Eventy
    {
        public static $filters = [];
        public static function addFilter($name, $callback, ...$args) { self::$filters[$name] = $callback; }
        public static function addAction($name, $callback, ...$args) {}
    }
    $checks = 0;
    function check($condition, $message)
    {
        $GLOBALS['checks']++;
        if (!$condition) { throw new \RuntimeException($message); }
    }
    function startsWith($actual, $expected) { return strpos($actual, $expected) === 0; }

    check(AI::promptSettings(null) === ['use_base' => true, 'prompt' => ''], 'New scopes inherit by default');
    check(AI::applyPrompt('global', ['use_base' => true, 'prompt' => 'extra']) === "global\n\nextra", 'Extension');
    check(AI::applyPrompt('global', ['use_base' => false, 'prompt' => 'replacement']) === 'replacement', 'Replacement');
    check(AI::applyPrompt('global', ['use_base' => false, 'prompt' => '  ']) === 'global', 'Empty legacy replacement is safe');

    $default = AI::prepareInstructions('draft_reply');
    check(startsWith($default, strtr(AI::defaultBasePrompt(), [':user_locale' => 'English'])), 'Default is the existing draft prompt');
    Option::$values['aiintegration.base_prompt'] = 'GLOBAL';
    check(startsWith(AI::prepareInstructions('draft_reply'), 'GLOBAL'), 'Stored base prompt is read from DB options');
    $mailbox = new TestMailbox();
    $mailbox->settings = ['use_base' => true, 'prompt' => 'MAILBOX'];
    check(startsWith(AI::prepareInstructions('draft_reply', [], $mailbox), "GLOBAL\n\nMAILBOX"), 'Mailbox extends global');
    $mailbox->settings['use_base'] = false;
    check(startsWith(AI::prepareInstructions('draft_reply', [], $mailbox), 'MAILBOX'), 'Mailbox replaces global');

    Option::$values['aiintegration.user_prompt.7'] = ['use_base' => true, 'prompt' => 'PERSONAL'];
    check(strpos(AI::prepareInstructions('draft_reply', [], $mailbox), 'PERSONAL') === false, 'Unprivileged personal prompt is ignored');
    $actor->allowed = true;
    check(startsWith(AI::prepareInstructions('draft_reply', [], $mailbox), "MAILBOX\n\nPERSONAL"), 'Personal extends mailbox');
    $mailbox->settings['use_base'] = true;
    check(startsWith(AI::prepareInstructions('draft_reply', [], $mailbox), "GLOBAL\n\nMAILBOX\n\nPERSONAL"), 'All three scopes compose');
    Option::$values['aiintegration.user_prompt.7']['use_base'] = false;
    $replacement = AI::prepareInstructions('draft_reply', ['EXTRA'], $mailbox);
    check(startsWith($replacement, 'PERSONAL') && strpos($replacement, 'GLOBAL') === false && strpos($replacement, 'MAILBOX') === false, 'Personal replacement clears all ancestors');
    check(strpos($replacement, 'reply_translation') !== false && strpos($replacement, 'EXTRA') !== false, 'Replacement preserves technical and contextual instructions');
    $actor->allowed = false;
    check(strpos(AI::prepareInstructions('draft_reply', [], $mailbox), 'PERSONAL') === false, 'Revocation takes effect immediately');
    $actor->admin = true;
    check(startsWith(AI::prepareInstructions('draft_reply'), 'PERSONAL'), 'Administrators may customize prompts');
    $actor = null;
    check(startsWith(AI::prepareInstructions('draft_reply'), 'GLOBAL'), 'Background requests do not use personal prompts');
    check(AI::prepareInstructions('summarize') === implode('. ', AI::$system_instructions['summarize']), 'Summary keeps its task instructions');
    check(AI::prepareInstructions('check_spelling') === implode('. ', AI::$system_instructions['check_spelling']), 'Editor actions keep their task instructions');

    Option::$values['aiintegration.base_prompt'] = '';
    check(startsWith(AI::prepareInstructions('draft_reply'), strtr(AI::defaultBasePrompt(), [':user_locale' => 'English'])), 'Empty base restores default');
    Option::$values['aiintegration.active'] = true;
    check((bool)AI::isActive(), 'Configured and enabled is active');
    $settings['aiintegration.enabled'] = false;
    check(!AI::isActive(), 'Manual disabling overrides valid configuration');
    $settings['aiintegration.enabled'] = true;
    Option::$values['aiintegration.active'] = false;
    check(!AI::isActive(), 'Enabling cannot bypass missing configuration');

    $actor = new TestUser();
    $provider = new AI();
    $provider->hooks();
    $permissions = Eventy::$filters['user_permissions.list'](['existing']);
    check(in_array(AI::PERM_EDIT_PROMPT, $permissions, true), 'Permission is registered with native user permissions');
    $request = new class {
        public function has($key) { return true; }
    };
    $blocked = false;
    try {
        Eventy::$filters['user.save_profile']($actor, $request);
    } catch (\RuntimeException $error) {
        $blocked = $error->getMessage() === 'HTTP 403';
    }
    check($blocked, 'Forged personal prompt submission without permission is rejected');
    $actor->allowed = true;
    $other = new TestUser();
    $other->id = 8;
    $blocked = false;
    try {
        Eventy::$filters['user.save_profile']($other, $request);
    } catch (\RuntimeException $error) {
        $blocked = $error->getMessage() === 'HTTP 403';
    }
    check($blocked, 'Personal permission does not permit editing another user');
    $controller = new \Modules\AiIntegration\Http\Controllers\AiIntegrationController();
    $blocked = false;
    try {
        $controller->mailboxSettings(1);
    } catch (\RuntimeException $error) {
        $blocked = $error->getMessage() === 'HTTP 403';
    }
    check($blocked, 'Mailbox settings require administrator access');
    echo "Passed $checks prompt and activation checks.\n";
}
