<?php

namespace Modules\AiIntegration\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AiIntegrationController extends Controller
{
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

        switch ($request->action) {

            case 'summarize':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation || !$auth_user->can('view', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $result = \AiIntegration::summarize($conversation);

                    $error = '';

                    if ($result['status'] == 'success' && !empty($result['data'])) {
                        $response['status'] = 'success';
                        $response['summary'] = $result['data'];
                    } else {
                        $response['msg'] = __('Error occurred. Please try again later.');
                        \AiIntegration::logApiError($error.' Response: '.json_encode($result), \AiIntegration::METHOD_CHAT);
                    }
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
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
                } else {
                    $response['msg'] = '[Load Models] '.$result['msg'];
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        $auth_user = auth()->user();

        switch ($request->action) {
            case 'generate_reply':
                $conversation = Conversation::find($request->param);

                if (!$conversation || !$auth_user->can('view', $conversation)) {
                    \Helper::denyAccess();
                }

                $result = \AiIntegration::draftReply($conversation);

                $reply = '';
                $translation = '';
                $error = '';

                if ($result['status'] == 'success' 
                    && !empty($result['data'])
                    && !empty($result['data']['reply'])
                ) {
                    $reply = $result['data']['reply'];
                    $translation = $result['data']['reply_translation'] ?? '';
                } else {
                    $error = __('Error occurred. Please try again later.');
                    \AiIntegration::logApiError($error.' Response: '.json_encode($result), \AiIntegration::METHOD_CHAT);
                }

                $response = response()->view('aiintegration::ajax_html/generate_reply', [
                    'reply' => $reply,
                    'translation' => $translation,
                    'error' => $error,
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
