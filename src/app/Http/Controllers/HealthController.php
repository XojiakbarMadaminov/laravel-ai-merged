<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function show()
    {
        return response()->json([
            'ok' => true,
            'model' => env('DEFAULT_MODEL', 'gpt-oss:20b'),
        ]);
    }
}
