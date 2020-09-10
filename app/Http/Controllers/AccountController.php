<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use App\Http\Requests\Account\ChangeEmail as ChangeEmailRequest;
use App\Http\Requests\Account\ChangePassword as ChangePasswordRequest;
use App\Http\Requests\Account\SetTasksOptions as SetTasksOptionsRequest;
use App\Http\Requests\Account\Delete as DeleteRequest;

class AccountController extends Controller
{
    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function account()
    {
        return $this->accountService->account();
    }

    public function closeAlert($type) {
        return $this->accountService->closeAlert($type);
    }

    public function changeEmail(ChangeEmailRequest $request)
    {
        return $this->accountService->changeEmail($request);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        return $this->accountService->changePassword($request);
    }

    public function setTasksOptions(SetTasksOptionsRequest $request)
    {
        return $this->accountService->setTasksOptions($request);
    }

    public function deleteAccount(DeleteRequest $request)
    {
        return $this->accountService->deleteAccount($request);
    }
}
