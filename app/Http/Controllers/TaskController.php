<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TaskService;
use App\Team;
use App\Task;
use App\Http\Requests\Task\SavePrivate as TaskSavePrivateRequest;
use App\Http\Requests\Task\SavePublic as TaskSavePublicRequest;
use App\Http\Requests\Task\Update as TaskUpdateRequest;
use App\Http\Requests\Task\Autosave as TaskAutosaveRequest;
use App\Http\Requests\Task\Draft as TaskDraftRequest;
use App\Http\Requests\Comment\Create as CommentCreateRequest;
use App\Http\Requests\Task\Attributes as TaskAttributesRequest;
use App\Http\Requests\Task\Search as TaskSearchRequest;
use App\Http\Requests\Task\MassSetAttributes as TaskMassSetAttributesRequest;
use App\History;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function publicUnsubscribe($hash)
    {
        return $this->taskService->publicUnsubscribe($hash);
    }

    public function createDraft(TaskDraftRequest $request, Team $team)
    {
        return $this->taskService->createDraft($request, $team);
    }

    public function createPrivateTask(TaskSavePrivateRequest $request, Team $team)
    {
        return $this->taskService->createPublicTask($request, $team);
    }

    public function createPublicTask(TaskSavePublicRequest $request, Team $team)
    {
        return $this->taskService->createPublicTask($request, $team);
    }

    public function setMass(TaskMassSetAttributesRequest $request, Team $team)
    {
        return $this->taskService->setMass($request, $team);
    }

    public function updateTask(TaskUpdateRequest $request, Team $team, Task $task)
    {
        return $this->taskService->updateTask($request, $team, $task);
    }

    public function autosaveTask(TaskAutosaveRequest $request, Team $team, Task $task)
    {
        return $this->taskService->autosaveTask($request, $team, $task);
    }

    public function deleteAutosaveTask(Request $request, Team $team, Task $task)
    {
        return $this->taskService->deleteAutosaveTask($request, $team, $task);
    }

    public function restoreAutosaveTask(Request $request, Team $team, Task $task)
    {
        return $this->taskService->restoreAutosaveTask($request, $team, $task);
    }

    public function getItem(Team $team, Task $task)
    {
        return $this->taskService->getItem($team, $task);
    }

    public function saveCommentsstate(Team $team, Task $task, Request $request)
    {
        return $this->taskService->saveCommentsstate($team, $task, $request);
    }

    public function deleteItem(Team $team, Task $task)
    {
        return $this->taskService->deleteItem($team, $task);
    }

    public function restoreItem(Team $team, Task $task)
    {
        return $this->taskService->restoreItem($team, $task);
    }

    public function deleteDraftForce(Team $team, Task $task)
    {
        return $this->taskService->deleteDraftForce($team, $task);
    }

    public function getAttributes(Team $team, Task $task)
    {
        return $this->taskService->getAttributes($team, $task);
    }

    public function setAttributes(TaskAttributesRequest $request, Team $team, Task $task)
    {
        return $this->taskService->setAttributes($request, $team, $task);
    }

    public function getItemHistory(Team $team, Task $task)
    {
        return $this->taskService->getItemHistory($team, $task);
    }

    public function setItemHistory(Team $team, Task $task, History $history)
    {
        return $this->taskService->setItemHistory($team, $task, $history);
    }

    public function createComment(CommentCreateRequest $request, Team $team, Task $task)
    {
        return $this->taskService->createComment($request, $team, $task);
    }

    public function subscribe(Team $team, Task $task)
    {
        return $this->taskService->subscribe($team, $task);
    }

    public function unsubscribe(Team $team, Task $task)
    {
        return $this->taskService->unsubscribe($team, $task);
    }

    public function search(TaskSearchRequest $request, Team $team)
    {
        return $this->taskService->search($request, $team);
    }
}
