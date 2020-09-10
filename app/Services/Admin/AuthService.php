<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use App\Services\CoreService;
use GuzzleHttp\Client;
use App\User;

class AuthService extends CoreService
{
    public function login(Request $request)
    {
        $data = $request->only(['name', 'password']);

        if (auth()->attempt($data)) {

            $me = auth()->user();

            if ( ! $me->isAdmin()) {
                customThrow('Incorrect login or password', 422);
            }

            customThrowIf( ! $me->isActive(), 'Account is not activated', 422);
            
            $data['email'] = $me->email;
            $tokens = $this->getPassportTokens($data);

            return response()->result($tokens);

        } else {
            customThrow('Incorrect login or password', 422);
        }
    }

    protected function getPassportTokens(array $data)
    {
        $client = new Client;

        $passwordParams = [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => config('auth.passport.client_id'),
                'client_secret' => config('auth.passport.client_secret'),
                'username' => array_get($data, 'email'),
                'password' => array_get($data, 'password'),
                'scope' => '*',
            ],
            'http_errors' => false,
        ];

        $response = $client->post(url('oauth/token'), $passwordParams);

        $result = json_decode($response->getBody()->getContents());

        if (empty($result->access_token) && ! empty($result->message)) {
            customThrow($result->message, 422);
        }

        return $result;
    }
}
