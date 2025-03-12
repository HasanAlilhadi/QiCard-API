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
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
