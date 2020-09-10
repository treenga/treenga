<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TeamService;
use App\User;
use App\Team;
use App\Http\Requests\Team\Create as TeamCreateRequest;
use App\Http\Requests\Team\Update as TeamUpdateRequest;
use App\Http\Requests\Team\ChangeName as TeamChangeNameRequest;
use App\Http\Requests\Team\AddUser as TeamAddUserRequest;
use App\Http\Requests\Team\GetNotifications as TeamGetNotificationsRequest;
use App\Http\Requests\Team\GetLastViewed as TeamGetLastViewedRequest;
use App\Http\Requests\Team\GetDrafts as TeamGetDraftsRequest;
use App\Http\Requests\Team\GetUnsorted as TeamGetUnsortedRequest;

class TeamController extends Controller
{
    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function list()
    {
        return $this->teamService->list();
    }

    public function getTeam(Team $team)
    {
        return $this->teamService->getTeam($team);
    }

    public function create(TeamCreateRequest $request)
    {
        return $this->teamService->create($request);
    }

    public function update(TeamUpdateRequest $request, Team $team)
    {
        return $this->teamService->update($request, $team);
    }

    public function deleteTeam(Team $team)
    {
        return $this->teamService->deleteTeam($team);
    }

    public function setFilter(Team $team, Request $request)
    {
        return $this->teamService->setFilter($team, $request);
    }

    public function setTreestate(Team $team, Request $request)
    {
        return $this->teamService->setTreestate($team, $request);
    }

    public function setLastTask(Team $team, Request $request)
    {
        return $this->teamService->setLastTask($team, $request);
    }

    public function autocomplite(Request $request, Team $team)
    {
        return $this->teamService->autocomplite($request, $team);
    }

    public function changeName(TeamChangeNameRequest $request, Team $team)
    {
        return $this->teamService->changeName($request, $team);
    }

    public function addUser(TeamAddUserRequest $request, Team $team)
    {
        return $this->teamService->addUser($request, $team);
    }
    public function deleteUser(Team $team, User $user)
    {
        return $this->teamService->deleteUser($team, $user);
    }

    public function info(Team $team)
    {
        return $this->teamService->info($team);
    }

    public function shortInfo(Team $team)
    {
        return $this->teamService->shortInfo($team);
    }

    public function getAllTask(Team $team)
    {
        return $this->teamService->getAllTask($team);
    }

    public function getLastViewed(Team $team, TeamGetLastViewedRequest $request)
    {
        return $this->teamService->getLastViewed($team, $request);
    }

    public function getNotifications(Team $team, TeamGetNotificationsRequest $request)
    {
        return $this->teamService->getNotifications($team, $request);
    }

    public function getDrafts(Team $team, TeamGetDraftsRequest $request)
    {
        return $this->teamService->getDrafts($team, $request);
    }

    public function getUnsortedTasks(Team $team, TeamGetUnsortedRequest $request)
    {
        return $this->teamService->getUnsortedTasks($team, $request);
    }

    public function getPrivateTasks(Team $team)
    {
        return $this->teamService->getPrivateTasks($team);
    }

    public function getPublicTasks(Team $team)
    {
        return $this->teamService->getPublicTasks($team);
    }

    public function getAssignedTask(Team $team)
    {
        return $this->teamService->getAssignedTask($team);
    }

    public function getUnassignedTask(Team $team)
    {
        return $this->teamService->getUnassignedTask($team);
    }

    public function getTasksByUser(Team $team, User $user)
    {
        return $this->teamService->getTasksByUser($team, $user);
    }

    public function getTasksByAuthor(Team $team, User $user)
    {
        return $this->teamService->getTasksByAuthor($team, $user);
    }
}
