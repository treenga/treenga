<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Hash as HashModel;
use App\Repositories\UserRepository;
use App\Http\Resources\User\Account as UserAccountResource;
use App\Events\Auth\ChangeEmail as AuthChangeEmailEvent;
use App\Events\Team\Delete as TeamDeleteEvent;
use App\Jobs\Account\Delete as AccountDeleteJob;
use App\Jobs\Team\Delete as TeamDeleteJob;

class AccountService extends CoreService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function account()
    {
        $me = auth()->user();

        $result = (new UserAccountResource($me));
        return response()->result($result, '');
    }

    public function closeAlert($type) {
        return response()->result(true);
    }

    public function changeEmail(Request $request)
    {
        $me = auth()->user();

        $checkPass = Hash::check($request->password, $me->password);
        customThrowIf( ! $checkPass, 'Wrong password');

        $hash = $this->userRepository->addRandomHash($me, HashModel::TYPE_CHANGE_EMAIL);
        $verifyLink = str_replace('__hash__', $hash, $request->link);

        $me = $this->userRepository->itemUpdate($me, ['new_email' => $request->email, 'email_verified_at' => null]);

        event(new AuthChangeEmailEvent($me, $verifyLink));

        return response()->result(new UserAccountResource($me), 'Verification email sent');
    }

    public function changePassword(Request $request)
    {
        $me = auth()->user();

        $check = Hash::check($request->old_password, $me->password);
        customThrowIf( ! $check, 'Wrong existing password');

        $me = $this->userRepository->itemUpdate($me, ['password' => bcrypt($request->new_password)]);

        return response()->result(new UserAccountResource($me), 'Password changed');
    }

    public function setTasksOptions(Request $request)
    {
        $me = auth()->user();

        $dataUser = $request->validated();
        $me = $this->userRepository->itemUpdate($me, $dataUser);

        return response()->result(new UserAccountResource($me));
    }

    public function deleteAccount(Request $request)
    {
        $me = auth()->user();

        $check = Hash::check($request->password, $me->password);
        customThrowIf( ! $check, 'Wrong password');

        $me = $this->userRepository->itemUpdate($me, ['status' => User::STATUS_DELETED]);

        $userTeams = $me->teams()->where('is_owner', true)->get();
        foreach($userTeams as $team) {
            event(new TeamDeleteEvent($team, $me));
            dispatch(new TeamDeleteJob($team));
        }

        dispatch(new AccountDeleteJob($me));

        return response()->result(true, 'Account deleted');
    }
}
