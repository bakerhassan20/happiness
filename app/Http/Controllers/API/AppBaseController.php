<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Response;

use App\Http\Controllers\Controller;


class AppBaseController extends Controller
{
    public function sendResponse($result, $message)
    {
        return Response::json(
            [
            'success' => true,
            'data'    => $result,
            'message' => $message
            ]
        );
    }

    public function sendError($error, $code = 404)
    {
        $res = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($code)) {
            $res['data'] = $code;
        }

        return Response::json($res);
    }

    public function sendSuccess($message)
    {
        return Response::json([
            'success' => true,
            'message' => $message
        ], 200);
    }

    function json_custom_response( $response, $status_code = 200 )
    {
        return response()->json($response,$status_code);
    }


}
