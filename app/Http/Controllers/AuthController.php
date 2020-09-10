<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Requests\Auth\Login as AuthLoginRequest;
use App\Http\Requests\Auth\Register as AuthRegisterRequest;
use App\Http\Requests\Auth\Recovery as AuthRecoveryRequest;
use App\Http\Requests\Auth\Reset as AuthResetRequest;
use App\Http\Requests\Auth\Invite as AuthInviteRequest;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(AuthLoginRequest $request)
    {
        return $this->authService->login($request);
    }

    public function logout()
    {
        return $this->authService->logout();
    }

    public function register(AuthRegisterRequest $request)
    {
        return $this->authService->register($request);
    }

    public function verify(Request $request, $hash)
    {
        return $this->authService->verify($request, $hash);
    }

    public function recovery(AuthRecoveryRequest $request)
    {
        return $this->authService->recovery($request);
    }

    public function reset(AuthResetRequest $request)
    {
        return $this->authService->reset($request);
    }

    public function upload(Request $request)
    {
        return $this->authService->upload($request);
    }

    public function getInfoByInviteHash(Request $request, $hash)
    {
        return $this->authService->getInfoByInviteHash($request, $hash);
    }

    public function setPasswordForInviteUser(AuthInviteRequest $request, $hash)
    {
        return $this->authService->setPasswordForInviteUser($request, $hash);
    }
}
