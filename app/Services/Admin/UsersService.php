<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\User\Account as UserAccountResource;
use App\Repositories\Admin\UserRepository;
use App\Repositories\UserRepository as MainUserRepository;
use App\Services\CoreService;
use App\User;
use App\Http\Resources\Admin\User\Short as UserShortResource;
use App\Http\Resources\Admin\User\Detail as UserDetailResource;
use App\Events\Auth\Admin\AddNewUser;
use App\Hash as AppHash;
use Illuminate\Support\Facades\Hash;

class UsersService extends CoreService
{
    protected $userRepository;
    protected $mainUserRepository;

    public function __construct(
        UserRepository $userRepository,
        MainUserRepository $mainUserRepository
        ) {
        $this->userRepository = $userRepository;
        $this->mainUserRepository = $mainUserRepository;
    }

    public function getAll()
    {
        $result = $this->userRepository->getAll();
        return response()->result(UserShortResource::collection($result), "");
    }

    public function getOne(Request $request, User $user)
    {
        $result = $this->userRepository->getOne($user);
        return response()->result(new UserDetailResource($result), "");
    }

    public function changePassword(Request $request)
    {
        $me = auth()->user();

        $checkOldPassword = Hash::check($request->old_password, $me->password);
        customThrowIf(!$checkOldPassword, 'Wrong existing password');

        customThrowIf($request->new_password !== $request->new_password_confirmation, 'New password is not confirmed');

        $me = $this->userRepository->itemUpdate($me, ['password' => bcrypt($request->new_password)]);

        return response()->result(new UserAccountResource($me), 'Password changed');
    }

    public function addUser(Request $request)
    {
        $me = auth()->user();

        $user = $this->mainUserRepository->getItemByEmail($request->email);
        
        $dataUser = [
            'email' => $request->email,
            'is_team_author' => $request->is_team_author,
        ];

        if ($user) {
            $user = $this->mainUserRepository->itemUpdate($user, $dataUser);
        } else {
            $dataUser += [
                'name' => $request->email,
                'password' => '',
                'status' => User::STATUS_INVITED,
            ];
            $user = $this->mainUserRepository->itemCreate($dataUser);

            $hash = $this->mainUserRepository->addRandomHash($user, AppHash::TYPE_TEAM_INVITE);
            $link = secure_url('invite/'.$hash);

            event(new AddNewUser($user, $link, $me));
        }
        return response()->result(new UserShortResource($user), $request->id 
            ? 'User updated'
            : 'Invitation sent');
    }
    
    public function deleteUser(Request $request)
    {
        $user = $this->mainUserRepository->getItemByEmail($request->email);
        
        customThrowIf(!$user, 'User is already deleted');

        $this->mainUserRepository->fullDeleteAccount($user);

        return response()->result(new UserShortResource($user), 'Account removed');
    }
}
