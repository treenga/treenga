<?php

namespace App\Services;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\User;
use App\Hash;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use App\Http\Resources\User\Short as UserShortResourse;
use App\Events\Auth\Register as AuthRegisterEvent;
use App\Events\Auth\Recovery as AuthRecoveryEvent;
use App\Events\Auth\Logout as AuthLogoutEvent;
use App\Events\Auth\Verify as AuthVerifyEvent;

class AuthService extends CoreService
{
    protected $userRepository;
    protected $teamRepository;

    public function __construct(
        UserRepository $userRepository,
        TeamRepository $teamRepository)
    {
        $this->userRepository = $userRepository;
        $this->teamRepository = $teamRepository;
    }

    public function login(Request $request)
    {
        $data = $request->only(['email', 'password']);

        if (auth()->attempt($data)) {
            $me = auth()->user();
            customThrowIf($me->isAdmin(), 'Incorrect email or password', 422);

            if (!$me->isActive()) {
                switch ($me->status) {
                    case User::STATUS_NEW:
                        $hash = $this->userRepository->addRandomHash($me, Hash::TYPE_VERIFY);
                        $verifyLink = str_replace('__hash__', $hash, $request->link);
                        event(new AuthRegisterEvent($me, $verifyLink));
                        customThrow('Account is not activated yet. Check your email and activate account', 422, 'warning');
                        break;
                    default:
                        customThrow('Incorrect email or password', 422);
                        break;
                }
            }

            $tokens = $this->getPassportTokens($data);

            return response()->result($tokens);

        } else {
            customThrow('Incorrect email or password', 422);
        }
    }

    public function logout()
    {
        $me = auth()->user();
        event(new AuthLogoutEvent($me));
        return response()->result(true);
    }

    public function register(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user && $user->isActive()) {
            customThrow('This email already in use.', 422, 'warning');
        }

        if(!$user) {
            $data = [
                'name' => '',
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'status' => User::STATUS_NEW,
                'version' => config('version.v'),
            ];

            $user = $this->userRepository->itemCreate($data);
        } else {
            $user = $this->userRepository->itemUpdate($user, [
                'password' => bcrypt($request->password),
            ]);
        }

        $hash = $this->userRepository->addRandomHash($user, Hash::TYPE_VERIFY);
        $verifyLink = str_replace('__hash__', $hash, $request->link);

        event(new AuthRegisterEvent($user, $verifyLink));

        return response()->result(true, 'Verification email sent');
    }

    public function verify(Request $request, string $hash)
    {
        $user = $this->userRepository->getItemByHash($hash, Hash::TYPE_VERIFY);

        if ($user) {
            $type = Hash::TYPE_VERIFY;
        } elseif ($user = $this->userRepository->getItemByHash($hash, Hash::TYPE_CHANGE_EMAIL)) {
            $type = Hash::TYPE_CHANGE_EMAIL;
        }

        customThrowIf(!$user, 'Wrong link');

        customThrowIf(!empty($user->email_verified_at), 'Your account is already verified');

        $data = [
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ];

        if($type == Hash::TYPE_CHANGE_EMAIL) {
            $data['email'] = $user->new_email;
            $data['new_email'] = null;
        } else {
            event(new AuthVerifyEvent($user));
        }

        $user = $this->userRepository->itemUpdate($user, $data);

        return $this->loginExistsUser($user);
    }

    public function recovery(Request $request)
    {
        $user = $this->userRepository->getItemByEmail($request->email);

        customThrowIf(( ! $user || ! $user->isActive()), 'Incorrect email', 422);

        $hash = $this->userRepository->addRandomHash($user, Hash::TYPE_RECOVERY);
        $recoveryLink = str_replace('__hash__', $hash, $request->link);

        event(new AuthRecoveryEvent($user, $recoveryLink));

        return response()->result(true, 'Reset link sent to your email');
    }

    public function reset(Request $request)
    {
        $user = $this->userRepository->getItemByHash($request->hash, Hash::TYPE_RECOVERY);

        customThrowIf( ! $user, 'Wrong link');

        $data = [
            'password' => bcrypt($request->password),
        ];

        $user = $this->userRepository->itemUpdate($user, $data);

        $passportData = array_merge(['email' => $user->email, 'password' => $request->password]);

        $tokens = $this->getPassportTokens($passportData);

        return response()->result($tokens, 'Password changed');
    }

    public function upload(Request $request)
    {
        $me = auth()->user();
        $slug = $request->get('team_slug');
        $team = false;
        if ($slug == 'private') {
            $team = $this->teamRepository->getPrivateTeam($me);
        } else {
            $team = $this->teamRepository->getItemBySlug($request->get('team_slug'));
        }

        if($request->hasFile('file') && $team) {
            $size = $request->file('file')->getClientSize();
            $path = $request->file('file')->store(
                'users/' . $me->id . '/' . $team->id,
                config('app.env') == 'production' ? 'linode' : 'public'
            );
            $team_id = $team->id;

            $dataFile = compact('size', 'path', 'team_id');

            $this->userRepository->addFile($me, $dataFile);
            $prefix = config('app.env') == 'production' ? 'https://userfiles.us-east-1.linodeobjects.com/' : '/storage/';
            return response()->result($prefix . $path);
        } else {
            customThrow('File not found');
        }
    }

    public function getInfoByInviteHash(Request $request, string $hash)
    {
        $user = $this->userRepository->getItemByHash($hash, Hash::TYPE_TEAM_INVITE);

        customThrowIf( ! $user, 'Wrong link');

        return response()->result(new UserShortResourse($user));
    }

    public function setPasswordForInviteUser(Request $request, string $hash)
    {
        $user = $this->userRepository->getItemByHash($hash, Hash::TYPE_TEAM_INVITE);

        customThrowIf( ! $user, 'Wrong link');

        $data = [
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'password' => bcrypt($request->password),
        ];

        $this->userRepository->deleteUserHash($user, Hash::TYPE_TEAM_INVITE);

        $user = $this->userRepository->itemUpdate($user, $data);

        $tokens = $this->getPassportTokens(['email' => $user->email, 'password' => $request->password]);

        event(new AuthVerifyEvent($user));

        return response()->result($tokens);
    }

    protected function loginExistsUser(User $user)
    {
        $oldPassword = $user->password;

        $tempPassword = str_random(10);

        $this->userRepository->itemUpdate($user, ['password' => bcrypt($tempPassword)]);

        $tokens = $this->getPassportTokens(['email' => $user->email, 'password' => $tempPassword]);

        $this->userRepository->itemUpdate($user, ['password' => $oldPassword]);

        return response()->result($tokens);
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

        if (empty($result->access_token) && !empty($result->message)) {
            customThrow($result->message, 422);
        }

        return $result;
    }
}
