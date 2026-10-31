<?php

namespace Modules\AiIntegration\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AiIntegrationController extends Controller
{ 
    public function mailboxSettings($id)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $mailbox = \App\Mailbox::findOrFail($id);
        return view('aiintegration::mailbox_settings', [
            'mailbox' => $mailbox,
            'prompt_settings' => \AiIntegration::mailboxPrompt($mailbox),
        ]);
    }

    public function saveMailboxSettings($id, Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $mailbox = \App\Mailbox::findOrFail($id);
        $settings = \AiIntegration::validatePrompt($request->input('aiintegration_prompt'));
        $mailbox->setMeta('aiintegration_prompt', $settings);
        $mailbox->save();
        return redirect()->route('mailboxes.aiintegration', ['id' => $mailbox->id])
            ->with('flash_success_floating', __('Settings saved'));
    }

    /**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $auth_user = auth()->user();

        if (!\AiIntegration::isActive()) {
            $response['msg'] = __('AI is not configured.');
            return \Response::json($response);
        }

        switch ($request->action) {

            case 'summarize':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation || !$auth_user->can('view', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $result = \AiIntegration::summarize($conversation);

                    if ($result['status'] == 'success' && !empty($result['data'])) {
                        $response['status'] = 'success';
                        $result['data'] = \AiIntegration::removeThinkingTags($result['data']);
                        $response['summary'] = htmlspecialchars($result['data']);
                    } else {
                        $response['msg'] = __('Error occurred. Please try again later.');
                        \AiIntegration::logApiError('Response: '.json_encode($result), \AiIntegration::METHOD_CHAT);
                    }
                }
                break;

            case 'draft_reply':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation || !$auth_user->can('view', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $instructions = is_string($request->instructions) ? trim($request->instructions) : '';
                $previous_draft = is_string($request->previous_draft) ? trim($request->previous_draft) : '';

                if (!$response['msg']) {
                    $response['msg'] = \AiIntegration::validateDraftInstructions($instructions, $previous_draft);
                }

                if (!$response['msg']) {
                    $result = \AiIntegration::draftReply($conversation, null, $instructions, $previous_draft);

                    $reply = '';
                    $translation = '';
                    $error = '';

                    if ($result['status'] == 'success' 
                        && !empty($result['data'])
                        && !empty($result['data']['reply'])
                    ) {
                        // Фильтруем черновик ответа и перевод от тегов think
                        $reply = \AiIntegration::removeThinkingTags($result['data']['reply']);
                        $translation = \AiIntegration::removeThinkingTags($result['data']['reply_translation'] ?? '');
                        $response['status'] = 'success';
                    } else {
                        $error = __('Error occurred. Please try again later.');
                        $response['msg'] = $error;
                        \AiIntegration::logApiError('Response: '.json_encode($result), \AiIntegration::METHOD_CHAT);
                    }

                    $response['html'] = \View::make('aiintegration::ajax_html/generate_reply', [
                        'reply' => $reply,
                        'translation' => $translation,
                        'error' => $error,
                    ])->render();
                }
                break;

            case 'assist':
                $result = \AiIntegration::assist($request->sub_action, $request->body);

                if ($result['status'] == 'success' && !empty($result['data'])) {
                    $response['status'] = 'success';
                    $result['data'] = \AiIntegration::removeThinkingTags($result['data']);
                    $response['body'] = \Helper::purifyHtml($result['data']);
                } else {
                    if (!empty($result['msg'])) {
                        $response['msg'] = $result['msg'];
                    } else {
                        $response['msg'] = __('Error occurred. Please try again later.');
                    }
                    \AiIntegration::logApiError(' Response: '.json_encode($result), \AiIntegration::METHOD_CHAT);
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occurred';
        }

        return \Response::json($response);
    }

    /**
     * Ajax controller.
     */
    public function ajaxAdmin(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            case 'load_models':
                $params = [
                    'provider' => $request->provider,
                    'api_key' => $request->api_key,
                    'base_url' => $request->base_url,
                ];
                $result = \AiIntegration::apiGetModels($params);

                if ($result['status'] == 'success' && !empty($result['models']) && is_array($result['models'])) {
                    $response['status'] = 'success';
                    $response['models'] = $result['models'];
                    \AiIntegration::cacheModels($result['models'], $params);
                } elseif (!empty($result['msg'])) {
                    $response['msg'] = '[Load Models] '.$result['msg'];
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occurred';
        }

        return \Response::json($response);
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        $auth_user = auth()->user();

        if (!\AiIntegration::isActive()) {
            \Helper::denyAccess();
        }

        switch ($request->action) {
            case 'draft_reply_form':
                $conversation = Conversation::find($request->param);

                if (!$conversation || !$auth_user->can('view', $conversation)) {
                    \Helper::denyAccess();
                }

                $response = response()->view('aiintegration::ajax_html/draft_reply_form', [
                    'conversation_id' => $conversation->id,
                    'auto_draft' => (bool)\AiIntegration::getSetting('auto_draft'),
                ]);

                return $this->disableCache($response);
        }

        abort(404);
    }

    // Disable browser caching.
    public function disableCache($response)
    {
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');

        return $response;
    }
}
