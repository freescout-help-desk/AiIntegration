<?php
// Standalone regression checks; framework/database/HTTP boundaries are simulated.
namespace Illuminate\Support { class ServiceProvider {} }
namespace Illuminate\Routing { class Controller {} }
namespace Illuminate\Http {
    class Request {
        public $action = 'summarize';
        public $conversation_id = 1;
        public function input($key) { return null; }
    }
}
namespace Illuminate\Database\Eloquent {
    #[\AllowDynamicProperties]
    class Model {
        public static $rows = [];
        public static function find($id) { return self::$rows[$id] ?? null; }
        public static function updateOrCreate($key, $values) {
            $row = new static();
            foreach (array_merge($key, $values) as $name => $value) { $row->$name = $value; }
            return self::$rows[$key['conversation_id']] = $row;
        }
    }
}
namespace App {
    class Thread {
        const TYPE_CUSTOMER = 1, TYPE_MESSAGE = 2, TYPE_NOTE = 3;
        public $id, $type, $body, $updated_at = '2026-10-31 12:00:00', $created_at = null;
        public function __construct($id, $type, $body) { $this->id = $id; $this->type = $type; $this->body = $body; }
        public function isCustomerMessage() { return $this->type == self::TYPE_CUSTOMER; }
        public function isNote() { return $this->type == self::TYPE_NOTE; }
        public function isUserMessage() { return $this->type == self::TYPE_MESSAGE; }
        public function getCreatedBy() { return new class { public function getFullName() { return 'Pat'; } }; }
        public function getBodyAsText() { return $this->body; }
    }
    class Conversation {
        public $id = 1, $subject = 'Help', $customer_email = 'pat@example.com', $number = 1, $customer = null;
        public $threads = [];
        public static function find($id) { return $GLOBALS['conversation']; }
        public function getThreads($skip, $take, $types) {
            return new \Items(array_values(array_filter($this->threads, function($thread) use ($types) { return in_array($thread->type, $types); })));
        }
    }
}
namespace Modules\AiIntegration\Providers {
    function curl_init() { return null; }
    function curl_setopt_array($ch, $options) {}
    function curl_getinfo($ch, $key) { return 200; }
    function curl_error($ch) { return ''; }
    function curl_errno($ch) { return 0; }
}
namespace {
    foreach (['CURLOPT_RETURNTRANSFER', 'CURLOPT_HTTPHEADER', 'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLINFO_HTTP_CODE'] as $i => $key) {
        if (!defined($key)) { define($key, $i); }
    }
    class Items implements \IteratorAggregate {
        private $items;
        public function __construct($items) { $this->items = $items; }
        public function getIterator(): \Traversable { return new \ArrayIterator($this->items); }
        public function map($callback) { return new self(array_map($callback, $this->items)); }
        public function values() { return $this; }
        public function toArray() { return $this->items; }
    }
    $settings = ['provider' => 'ollama', 'include_notes' => false, 'send_images' => false];
    $calls = 0; $allowed = true; $api_text = '- Saved summary'; $during_request = null;
    function config($key) { return $GLOBALS['settings'][substr($key, strlen('aiintegration.'))] ?? ''; }
    function auth() { return new class {
        public function user() { return new class {
            public $locale = 'en';
            public function can($ability, $conversation) { return $GLOBALS['allowed']; }
        }; }
    }; }
    function __($text) { return $text; }
    class Helper {
        public static function decrypt($value) { return $value; }
        public static function getLocaleData($locale, $key) { return 'English'; }
        public static function setCurlDefaultOptions($ch) {}
        public static function curlClose($ch) {}
        public static function curlExec($url, $ch) {
            $GLOBALS['calls']++;
            if ($GLOBALS['during_request']) { ($GLOBALS['during_request'])(); }
            return json_encode(['choices' => [['message' => ['content' => $GLOBALS['api_text']]]]]);
        }
        public static function log($name, $text) {}
    }
    class Log { public static function error($text) {} }
    class Option { public static function get($key) { return true; } }
    class Response { public static function json($response) { return $response; } }
    require __DIR__.'/../Entities/ConversationSummary.php';
    require __DIR__.'/../Providers/AiIntegrationServiceProvider.php';
    require __DIR__.'/../Http/Controllers/AiIntegrationController.php';
    class_alias('Modules\\AiIntegration\\Providers\\AiIntegrationServiceProvider', 'AiIntegration');
    function check($condition, $message) { if (!$condition) { throw new \RuntimeException($message); } echo "PASS: $message\n"; }

    $conversation = new \App\Conversation();
    $conversation->threads = [new \App\Thread(1, 1, 'Question')];
    $result = AiIntegration::summarize($conversation);
    check($calls === 1 && $result['summary']->summary === '- Saved summary', 'First generation saves text and a timestamp');
    check(preg_match('/^\d{4}-\d{2}-\d{2} /', $result['summary']->generated_at) && !$result['summary_outdated'], 'Generated summary is current and dated');
    AiIntegration::summarize($conversation);
    check($calls === 1, 'Reopening reuses saved summary without an API call');
    foreach ([2, 3, 1] as $type) {
        $conversation->threads[] = new \App\Thread(count($conversation->threads)+1, $type, 'New activity');
        check(AiIntegration::summarize($conversation)['summary_outdated'], 'New published thread marks summary outdated (type '.$type.')');
    }
    $api_text = '<think>private reasoning</think>- Updated';
    $result = AiIntegration::summarize($conversation, true);
    check($calls === 2 && $result['summary']->summary === '- Updated' && !$result['summary_outdated'], 'Manual refresh replaces summary and strips thinking tags');
    $conversation->threads[0]->body = 'Edited question';
    check(AiIntegration::summarize($conversation)['summary_outdated'], 'Editing a message invalidates the saved summary');
    $during_request = function() use ($conversation) { $conversation->threads[] = new \App\Thread(8, 2, 'Arrived during generation'); };
    check(AiIntegration::summarize($conversation, true)['summary_outdated'], 'Activity during API request cannot produce a current summary');
    $during_request = null;
    $saved = \Modules\AiIntegration\Entities\ConversationSummary::find(1);
    foreach (['', '<think>only reasoning</think>'] as $api_text) {
        $result = AiIntegration::summarize($conversation, true);
        check(!isset($result['summary']) && \Modules\AiIntegration\Entities\ConversationSummary::find(1) === $saved, 'Empty or unusable refresh preserves saved summary');
    }
    $allowed = false;
    $before = $calls;
    $response = (new \Modules\AiIntegration\Http\Controllers\AiIntegrationController())->ajax(new \Illuminate\Http\Request());
    check($response['status'] === 'error' && $calls === $before && !isset($response['html']), 'Ticket permission is required before reading or generating a summary');
}
