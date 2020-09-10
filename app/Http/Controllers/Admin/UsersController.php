<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\UsersService;
use App\User;
use App\Http\Requests\Admin\User\AddFounds as UserAddFoundsRequest;
use App\Http\Requests\Admin\User\ShowLink as UserShowLinkRequest;

class UsersController extends Controller
{
    protected $usersService;

    public function __construct(UsersService $usersService)
    {
        $this->usersService = $usersService;
    }

    public function getAll(Request $request)
    {
        return $this->usersService->getAll();
    }

    public function getOne(Request $request, User $user)
    {
        return $this->usersService->getOne($request, $user);
    }

    public function setOne(Request $request, User $user)
    {
        return $this->usersService->getOne($request, $user);
    }

    public function addFounds(UserAddFoundsRequest $request, User $user)
    {
        return $this->usersService->addFounds($request, $user);
    }

    public function setShowLink(UserShowLinkRequest $request, User $user)
    {
        return $this->usersService->setShowLink($request, $user);
    }

    public function getHistory(Request $request, User $user)
    {
        return $this->usersService->getHistory($request, $user);
    }

    public function changePassword(Request $request) {
        return $this->usersService->changePassword($request);
    }

    public function addUser(Request $request) {
        return $this->usersService->addUser($request);
    }

    public function deleteUser(Request $request) {
        return $this->usersService->deleteUser($request);
    }
}
