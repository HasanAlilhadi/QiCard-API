<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    public function success($data = [], $message = 'success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function message($message = 'success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }

    public function error($message = 'error', $status = 400)
    {
        if ($message === 'error') $message = 'Error';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function unauthorized($message = 'unauthorized', $status = 401)
    {
        if ($message === 'unauthorized') $message = 'Unauthorized';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function forbidden($message = 'forbidden', $status = 403)
    {
        if ($message === 'forbidden') $message = 'Forbidden';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function notFound($message = 'not_found', $status = 404)
    {
        if ($message === 'not_found') $message = 'Not Found';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
