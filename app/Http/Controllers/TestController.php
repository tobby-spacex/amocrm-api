<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    public function test()
    {
        $token = [];
        $token['access_token'] = Cache::get('access_token');
        $token['refresh_token'] = Cache::get('refresh_token');

        return env('AMO_CLIENT_ID');
    }
}
