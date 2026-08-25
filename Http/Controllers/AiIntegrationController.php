<?php

namespace Modules\AiIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AiIntegrationController extends Controller
{
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
                $result = \AiIntegration::apiGetModels();

                if ($result['status'] == 'success') {
                    $response['status'] = 'success';
                    $response['models'] = $result['models'];
                    //$response['models'] = ['11', '22'];
                } else {
                    $response['msg'] = $result['msg'] ?? '';
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
}
