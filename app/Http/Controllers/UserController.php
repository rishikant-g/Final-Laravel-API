<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Infrastructure\Auth\LoginProxy;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\UserCampaigns;

class UserController extends Controller
{
    protected $loginproxy;

    public function __construct(LoginProxy $loginproxy)
    {
        $this->loginproxy = $loginproxy;
    }


    public function login(Request $request)
    {
        return response()->json($this->loginproxy->attemptLogin($request->email, $request->password));
    }

    public function refreshToken(Request $request)
    {
        return response()->json($this->loginproxy->attemptRefresh($request->refresh_token));
    }

    public function logout(Request $request)
    {
        $response = [];
        if ($this->loginproxy->logout()) {
            $response = ["message" => "Logout successful", "success" => true];
        } else {
            $response = ["message" => "Logout unsuccessful", "success" => false];
        }
        return response()->json($response);
    }
}
