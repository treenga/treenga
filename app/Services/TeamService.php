<?php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\CategoryService;
use App\User;
use App\Team;
use App\Category;
use App\Notification;
use App\Hash;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use App\Repositories\CategoryRepository;
use App\Http\Resources\Team\Short as TeamShortResource;
use App\Http\Resources\Team\ShortWithCount as TeamShortWithCountResource;
use App\Http\Resources\Team\WithUsers as TeamWithUsersResource;
use App\Http\Resources\User\Autocomplite as UserAutocompliteResource;
use App\Http\Resources\Team\Merged as TeamMergedResource;
use App\Http\Resources\Team\ShortInfo as TeamShortInfoResource;
use App\Http\Resources\Team\MergedTeams as TeamMergedTeamsResource;
use App\Http\Resources\Task\ListItem as TaskListItemResource;
use App\Http\Resources\Task\ListCollection as TaskListCollection;
use App\Events\Team\AddUser as TeamAddUserEvent;
use App\Events\Team\DeleteUser as TeamDeleteUserEvent;
use App\Events\Team\AddNewUser as TeamAddNewUserEvent;
use App\Events\Team\Delete as TeamDeleteEvent;
use App\Events\Team\Edit as TeamEditEvent;
use App\Events\Team\GetTasks as TeamGetTasksEvent;
use App\Jobs\Team\Delete as TeamDeleteJob;
use App\Jobs\Account\UpdateUsername as AccountUpdateUsernameJob;

class TeamService extends CoreService
{
    protected $userRepository;

    protected $teamRepository;

    protected $categoryService;
    protected $categoryRepository;

    public function __construct(
        UserRepository $userRepository,
        TeamRepository $teamRepository,
        CategoryService $categoryService,
        CategoryRepository $categoryRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->teamRepository = $teamRepository;
        $this->categoryService = $categoryService;
        $this->categoryRepository = $categoryRepository;
    }

    public function list()
    {
        $me = auth()->user();
        $mergedTeams = $this->userRepository->loadTeamsWithNotify($me);

        $result = (new TeamMergedTeamsResource([]))->withCustomData($mergedTeams);
        return response()->result($result);
    }

    public function getTeam(Team $team)
    {
        $me = auth()->user();

        $team->load('users');

        $this->throwUserTeam($me, $team);

        return response()->result(new TeamWithUsersResource($team));
    }

    public function create(Request $request)
    {
        $me = auth()->user();

        $this->throwUserLock($me);
        $this->throwUserCantCreate($me);

        $dataTeam = $request->only('name');

        $team = $this->teamRepository->itemCreate($dataTeam);

        $pivotData = [
            'username' => $request->username,
            'is_owner' => true,
            'treestate' => $team->getDefaultTreestate(),
            'filter' => $team->getDefaultFilter(),
            'current_state' => $team->getDefaultCurrentState(),
        ];

        $team = $this->teamRepository->addUser($team, $me, $pivotData);

        $me = $this->userRepository->setCurrentTeam($me, $team);

        $team->load('users');

        return response()->result(new TeamWithUsersResource($team), 'Team created');
    }

    public function createPrivate(User $user) {
       if (!$user->hasPrivate()) {
            $team = $this->teamRepository->itemCreate([
                'name' => 'Private',
                'private' => true,
            ]);

            $pivotData = [
                'username' => 'Me',
                'is_owner' => true,
                'treestate' => $team->getDefaultTreestate(),
                'filter' => $team->getDefaultFilter(),
                'current_state' => $team->getDefaultCurrentState(),
            ];

            $team = $this->teamRepository->addUser($team, $user, $pivotData);

            $team->load('users');
       }
    }

    public function update(Request $request, Team $team)
    {
        $me = auth()->user();

        $me->load('teams');

        customThrowIf( ! $me->isTeamOwner($team), 'It is not your team');

        $dataTeam = $request->only('name');

        $team = $this->teamRepository->itemUpdate($team, $dataTeam);
        $username = $request->username;
        $oldUsername = $team->getUsername($me);
        $pivotData = [
            'username' => $username,
        ];

        $team = $this->teamRepository->updateUserPivot($team, $me, $pivotData);

        $team->load('users');

        event(new TeamEditEvent($team, $me));

        if($username !== $oldUsername) {
            dispatch(new AccountUpdateUsernameJob($me, $username));
        }

        return response()->result(new TeamWithUsersResource($team), 'Team updated');
    }

    public function deleteTeam(Team $team)
    {
        $me = auth()->user();
        $me->load('teams');
        customThrowIf( ! $me->isTeamOwner($team), 'It is not your team');
        $team->load('users');
        $team->delete();

        event(new TeamDeleteEvent($team, $me));

        TeamDeleteJob::dispatch($team);

        return response()->result(true, 'Team deleted');
    }

    public function setFilter(Team $team, Request $request)
    {
        $me = auth()->user();

        $pivotData = [
            'filter' => json_encode($request->all()),
        ];

        $team = $this->teamRepository->updateUserPivot($team, $me, $pivotData);

        return response()->result(true);
    }

    public function setTreestate(Team $team, Request $request)
    {
        $me = auth()->user();

        $pivotData = [
            'treestate' => json_encode($request->all()),
        ];

        $team = $this->teamRepository->updateUserPivot($team, $me, $pivotData);

        return response()->result(true);
    }

    public function setLastTask(Team $team, Request $request)
    {
        $me = auth()->user();

        $treestate = json_decode($team->users()->where('user_id', $me->id)->first()->pivot->treestate, true);
        $treestate['last_task'] = $request->task_id;

        $pivotData = [
            'treestate' => json_encode($treestate),
        ];

        $team = $this->teamRepository->updateUserPivot($team, $me, $pivotData);

        return response()->result(true);
    }

    public function autocomplite(Request $request, Team $team)
    {
        $me = auth()->user();

        $users = $this->userRepository->getUsersForAutocomplite($team, $me);

        return response()->result(UserAutocompliteResource::collection($users));
    }

    public function changeName(Request $request, Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $username = $request->username;
        $oldUsername = $team->getUsername($me);
        $pivotData = [
            'username' => $username,
        ];

        $team = $this->teamRepository->updateUserPivot($team, $me, $pivotData);
        $team->load('users');

        event(new TeamEditEvent($team, $me));
        if($username !== $oldUsername) {
            dispatch(new AccountUpdateUsernameJob($me, $username));
        }

        return response()->result(new TeamWithUsersResource($team), 'Name changed');
    }

    public function addUser(Request $request, Team $team)
    {
        $me = auth()->user();

        $team->load('users');

        customThrowIf( ! $team->hasOwner($me), 'It is not your team');

        $user = $this->userRepository->getItemByEmail($request->email);
        customThrowIf( $user && $team->users->contains('id', $user->id), 'User is already in this team');

        if( ! $user) {
            return $this->inviteNewUserInService($request, $team);
        }

        $pivotData = [
            'username' => $request->username,
            'treestate' => $team->getDefaultTreestate(),
            'filter' => $team->getDefaultFilter(),
            'current_state' => $team->getDefaultCurrentState(),
        ];

        $team = $this->teamRepository->addUser($team, $user, $pivotData);

        $team->load('users');

        event(new TeamAddUserEvent($team, $user, $me));

        return response()->result(new TeamWithUsersResource($team), 'User added');
    }

    public function inviteNewUserInService(Request $request, Team $team)
    {
        $me = auth()->user();

        $dataUser = [
            'email' => $request->email,
            'name' => $request->username,
            'password' => '',
            'status' => User::STATUS_INVITED,
        ];
        $user = $this->userRepository->itemCreate($dataUser);
        $pivotData = [
            'username' => $request->username,
            'treestate' => $team->getDefaultTreestate(),
            'filter' => $team->getDefaultFilter(),
            'current_state' => $team->getDefaultCurrentState(),
        ];
        $team = $this->teamRepository->addUser($team, $user, $pivotData);
        $team->load('users');

        $hash = $this->userRepository->addRandomHash($user, Hash::TYPE_TEAM_INVITE);
        $link = secure_url('invite/'.$hash);

        event(new TeamAddNewUserEvent($team, $user, $link, $me));

        return response()->result(new TeamWithUsersResource($team), 'Invitation sent');
    }

    public function deleteUser(Team $team, User $user)
    {
        $me = auth()->user();

        $team->load('users');

        customThrowIf( ! $team->hasOwner($me), 'It is not your team');

        customThrowIf( $team->hasOwner($user), 'Can\'t remove owner');

        customThrowIf( ! $team->users->contains('id', $user->id), 'User is not in this team');

        $this->teamRepository->detachUser($team, $user);
        $this->teamRepository->detachUserFromTasks($team, $user);
        $this->teamRepository->deletePrivateTasks($team, $user);
        $this->teamRepository->deletePrivateCategories($team, $user);

        $team->load('users');

        event(new TeamDeleteUserEvent($team, $user, $me));

        return response()->result(new TeamWithUsersResource($team), 'User removed');
    }

    public function info(Team $team)
    {
        $me = auth()->user();

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $this->throwUserTeam($me, $team);

        $counts = $this->teamRepository->loadCountsTasks($team, $me);

        $privateTree = $this->categoryRepository->getPrivateTreeWithCounts($team, $me, $withTrashed, $onlyTrashed);

        $publicTree = $this->categoryRepository->getPublicTreeWithCounts($team, $me, $withTrashed, $onlyTrashed);

        $teamUsers = $this->teamRepository->getUsersWithCountTasks($team, $me);

        $teamAuthors = $this->teamRepository->getAuthorUsersWithCountTasks($team, $me);

        $this->userRepository->setCurrentTeam($me, $team);

        return response()->result((new TeamMergedResource([]))->withCustomData(compact('counts', 'privateTree', 'publicTree', 'teamUsers', 'teamAuthors')));
    }

    public function shortInfo(Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);
        $team = $this->teamRepository->loadTeamShotInfo($team, $me);
        $team->privateCategories->toTree();
        $team->publicCategories->toTree();
        return response()->result(new TeamShortInfoResource($team));
    }

    public function getAllTask(Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks();
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $query->filter($this->getSearchParam());
        $tasks = $query->onlyUserPrivate($me)->forList($team, $me)->get();

        return response()->result(TaskListItemResource::collection($tasks));
    }

    public function getLastViewed(Team $team, Request $request)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = true;
        $onlyTrashed = false;
        
        $query = $me->lastViewed()->where('team_id', $team->id);
        $query->orderBy('user_task_viewed.created_at', 'DESC');
        $query->filter($this->getSearchParam());

        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'recently', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getNotifications(Team $team, Request $request)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = true;
        $onlyTrashed = false;

        $query = $team->tasks();
        $query->whereHas('userNotifications', function($q) use($me) {
            $q->where('notifiable_id', $me->id);
            $q->where('notifiable_type', 'App\User');
        });
        $query->with(['userNotifications' => function($q) {
          $q->orderBy('notifyables.created_at', 'DESC');
        } ]);
        $query->orderBy('updated_at', 'desc');
        $query->filter($this->getSearchParam());

        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'notifications', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getDrafts(Team $team, Request $request)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = true;
        $onlyTrashed = false;

        $query = $team->tasks()->filter($this->getSearchParam());
        $query->userDrafts($me);
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'drafts', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getUnsortedTasks(Team $team, Request $request)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks()->filter($this->getSearchParam());
        $query->noDraft()->noCats();
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'unsorted', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    //TODO
    public function getPrivateTasks(Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks()->filter($this->getSearchParam());
        $query->onlyUserPrivate($me)->private();
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'category', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getPublicTasks(Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks()->filter($this->getSearchParam());
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        $category_id = 'all-tasks';

        event(new TeamGetTasksEvent($team, $me, 'all-tasks', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count', 'category_id'));
        return response()->result($result);
    }

    public function getAssignedTask(Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $me->assignedTasks();
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $query->filter($this->getSearchParam());
        $tasks = $query->where('team_id', $team->id)->forList($team, $me)->get();

        return response()->result(TaskListItemResource::collection($tasks));
    }

    public function getUnassignedTask(Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks()->filter($this->getSearchParam());
        $query->doesntHave('users');
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;

        $tasks = $query->forList($team, $me)->get();
        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'unassigned', $onlyTrashed));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getTasksByUser(Team $team, User $user)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);
        $this->throwUserTeam($user, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks()->filter($this->getSearchParam());
        $query->whereHas('users', function($q) use($user){
            $q->where('user_id', $user->id);
        });
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'by_user', $onlyTrashed, $user->id));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getTasksByAuthor(Team $team, User $user)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);
        $this->throwUserTeam($user, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $team->tasks()->filter($this->getSearchParam());
        $query->whereHas('author', function($q) use($user){
            $q->where('id', $user->id);
        });
        $baseQuery = clone $query;

        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;

        $tasks = $query->forList($team, $me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        event(new TeamGetTasksEvent($team, $me, 'by_author', $onlyTrashed, $user->id));

        $result = (new TaskListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));

        return response()->result($result);
    }

    public function removeOldNotifications()
    {
        Notification::where('read_at', '<', Carbon::now()->subDay(3))->delete();
    }

    protected function formatDueDates($items)
    {
        $result = [
            'no' => 0,
            'overdue' => 0,
            'today' => 0,
            'tommorow' => 0,
            'thisWeek' => 0,
            'nextWeek' => 0,
            'thisMonth' => 0,
            'nextMonth' => 0,
        ];

        return $result;
    }

    protected function formatDueDatesOld($items)
    {
        $result['no'] = $items->where('due_date', null)->count();
        $result['overdue'] = $items->where('due_date', '<', now())->count();
        $result['today'] = $items->where('due_date', '>=', now()->startOfDay())->where('due_date', '<=', now()->endOfDay())->count();
        $result['tommorow'] = $items->where('due_date', '>=', now()->addDay(1)->startOfDay())->where('due_date', '<=', now()->addDay(1)->endOfDay())->count();
        $result['thisWeek'] = $items->where('due_date', '>=', now()->startOfWeek()->startOfDay())->where('due_date', '<=', now()->endOfWeek()->endOfDay())->count();
        $result['nextWeek'] = $items->where('due_date', '>=', now()->addWeek(1)->startOfWeek()->startOfDay())->where('due_date', '<=', now()->addWeek(1)->endOfWeek()->endOfDay())->count();
        $result['thisMonth'] = $items->where('due_date', '>=', now()->startOfMonth()->startOfDay())->where('due_date', '<=', now()->endOfMonth()->endOfDay())->count();
        $result['nextMonth'] = $items->where('due_date', '>=', now()->addMonth(1)->startOfMonth()->startOfDay())->where('due_date', '<=', now()->addMonth(1)->endOfMonth()->endOfDay())->count();

        return $result;
    }
}
