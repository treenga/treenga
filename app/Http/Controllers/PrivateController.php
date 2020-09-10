<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PrivateService;
use App\Category;
use App\Task;
use App\Http\Requests\Category\Save as CategorySaveRequest;
use App\Http\Requests\Category\Move as CategoryMoveRequest;
use App\Http\Requests\Task\PrivateDraft as TaskPrivateDraftRequest;
use App\Http\Requests\Task\PrivateCreate as TaskPrivateCreateRequest;
use App\Http\Requests\Task\PrivateUpdate as TaskPrivateUpdateRequest;

class PrivateController extends Controller
{
    protected $privateService;

    public function __construct(PrivateService $privateService)
    {
        $this->privateService = $privateService;
    }

    public function info(Request $request)
    {
        return $this->privateService->info($request);
    }

    public function getTasks(Request $request)
    {
        return $this->privateService->getTasks($request);
    }

    public function getUnsortedTasks(Request $request)
    {
        return $this->privateService->getUnsortedTasks($request);
    }

    public function getDrafts(Request $request)
    {
        return $this->privateService->getDrafts($request);
    }

    public function getCategoryTasks(Request $request, Category $category)
    {
        return $this->privateService->getCategoryTasks($request, $category);
    }

    public function addCategory(CategorySaveRequest $request)
    {
        return $this->privateService->addCategory($request);
    }

    public function moveCategory(CategoryMoveRequest $request, Category $category)
    {
        return $this->privateService->moveCategory($request, $category);
    }

    public function updateCategoryName(CategorySaveRequest $request, Category $category)
    {
        return $this->privateService->updateCategoryName($request, $category);
    }

    public function getTask(Request $request, Task $task)
    {
        return $this->privateService->getTask($request, $task);
    }

    public function createDraft(TaskPrivateDraftRequest $request)
    {
        return $this->privateService->createDraft($request);
    }

    public function createTask(TaskPrivateCreateRequest $request)
    {
        return $this->privateService->createTask($request);
    }

    public function updateTask(TaskPrivateUpdateRequest $request, Task $task)
    {
        return $this->privateService->updateTask($request, $task);
    }
}
