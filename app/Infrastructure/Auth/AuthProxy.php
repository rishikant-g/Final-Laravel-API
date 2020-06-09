<?php
/**
 * Created by PhpStorm.
 * User: risheekant
 * Date: 20/2/19
 * Time: 10:35 AM
 */

namespace App\Infrastructure\Auth;

use App\Exceptions\InactiveUserException;
use App\Exceptions\InvalidCredentialsException;
use GuzzleHttp\Client;
use App\User;
use Illuminate\Foundation\Application;
use DB;

class LoginProxy
{

    const REFRESH_TOKEN = 'refreshToken';

    private $httpClient;
    private $auth;
    private $cookie;
    private $db;
    private $request;
    private $user;

    public function __construct(
        Application $app,
        User $userModel,
        Client $httpClient
    ) {
        $this->user = $userModel;
        $this->httpClient = $httpClient;
        $this->auth = $app->make('auth');
        $this->cookie = $app->make('cookie');
        $this->db = $app->make('db');
        $this->request = $app->make('request');
    }

    /**
     * Attempt to create an access token using user credentials
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     */
    public function attemptLogin($email, $password)
    {

        $user = $this->user->where('email', $email)->first();

        if (!is_null($user)) {
            $response = $this->proxy('password', [
                'username' => $email,
                'password' => $password
            ]);

            // if ($user->is_active != 1) {
            //     throw new InactiveUserException();
            //     // return response()->json(['status' => false, 'message' => 'Inactive user']);
            // }

            // $logindetails = new \App\LoginDetails();
            // $logindetails->user_id = $user->id;
            // $logindetails->login_at = \Carbon\Carbon::now();
            // $logindetails->logout_at = \Carbon\Carbon::now();
            // $logindetails->save();

            $response['name'] = $user->name;
            // $user_profile = $user->profile_picture;

            // if (empty($user_profile)) {
            //     $response['profile'] = 'userprofile/user.jpg';
            // } else {
            //     $response['profile'] = $user_profile;
            // }
            
            return $response;
        }

        throw new InvalidCredentialsException();
//        return response()->json(["status" => false, "message" => "invalid credential"]);
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param string $grantType what type of grant type should be proxied
     * @param array $data the data to send to the server
     *
     * @return array
     */
    public function proxy($grantType, array $data = [])
    {

        $client = $this->db->table('oauth_clients')->where('password_client', '=', '1')->first();
        $data = array_merge($data, [
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'grant_type' => $grantType
        ]);

        $url = (env('APP_ENV') == 'local') ? 'http://api.local/oauth/token' : url('/oauth/token');
        \Log::debug($url);
        try {
            $response = $this->httpClient->post(
                $url, ['form_params' => $data]
            );
        } catch (\Exception $e) {
            \Log::error("Error while trying to post to the oauth server. " . $e->getMessage());
            throw new InvalidCredentialsException();
        }

        if ($response->getStatusCode() != "200") {
            throw new InvalidCredentialsException();
        }

        $data = json_decode($response->getBody());


        return [
            'refresh_token' => $data->refresh_token,
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in
        ];
    }

    /**
     * Attempt to refresh the access token used a refresh token that
     * has been saved in a cookie
     */
    public function attemptRefresh()
    {
        $refreshToken = $this->request->get('refresh_token');

        return $this->proxy('refresh_token', [
            'refresh_token' => $refreshToken
        ]);
    }

    /**
     * Logs out the user. We revoke access token and refresh token.
     * Also instruct the client to forget the refresh cookie.
     */
    public function logout()
    {
        $accessToken = $this->auth->user()->token();

        $this->db
            ->table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);

        if ($accessToken->revoke()) {
           // $user = $this->auth->user();
          //  $logoutat = \Carbon\Carbon::now();
          //  $check = DB::select(DB::raw("UPDATE login_details SET logout_at='" . $logoutat . "' WHERE user_id='" . $user->id . "' ORDER BY id DESC LIMIT 1"));

            return true;
        } else {
            return false;
        }

    }

}

