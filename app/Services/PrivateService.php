<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repositories\CategoryRepository;
use App\Repositories\TaskRepository;
use App\Category;
use App\Task;
use App\Http\Resources\Personal\Info as PersonalInfoResourse;
use App\Http\Resources\Task\PrivateListCollection;
use App\Http\Resources\Category\MergedPrivateTaskList;
use App\Http\Resources\Category\Short as CategoryShortResourse;
use App\Http\Resources\Task\PrivateDetail as TaskPrivateDetailResource;
use App\Http\Resources\Task\NewDraft as TaskNewDraftResource;
use App\Http\Resources\Task\Short as TaskShortResource;

class PrivateService extends CoreService
{
    protected $categoryRepository;
    protected $taskRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        TaskRepository $taskRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->taskRepository = $taskRepository;
    }

    public function info(Request $request)
    {
        $me = auth()->user()->load(['categories' => function($q){
            $q->orderBy('name');
        }]);
        $categories = Category::privateUser($me)->get();
        $privateTree = $me->categories->toTree();

        $result = (new PersonalInfoResourse([]))->withCustomData(compact('privateTree'));
        return response()->result($result);
    }

    public function getTasks(Request $request)
    {
        $me = auth()->user();

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $me->authoredTasks()->private()->filter($this->getSearchParam());
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forPrivateList($me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        $result = (new PrivateListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getUnsortedTasks(Request $request)
    {
        $me = auth()->user();

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $me->authoredTasks()->private()->filter($this->getSearchParam());
        $query->noDraft()->noCats();
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forPrivateList($me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        $result = (new PrivateListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getDrafts(Request $request)
    {
        $me = auth()->user();

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $query = $me->authoredTasks()->private()->draft()->filter($this->getSearchParam());

        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forPrivateList($me)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        $result = (new PrivateListCollection($tasks))->withCustomData(compact('tasks_count', 'deleted_tasks_count'));
        return response()->result($result);
    }

    public function getCategoryTasks(Request $request, Category $category)
    {
        $me = auth()->user();

        $this->throwNotUserCategory($me, $category);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $search = $this->getSearchParam();

        $currentData = $this->categoryRepository->getCurrentPrivateTasks($category, $me, $search, $withTrashed, $onlyTrashed);
        $currentTaskIds = array_get($currentData, 'tasks')->pluck('id')->toArray();

        $childData = $this->categoryRepository->getChildPrivateTasks($category, $me, $currentTaskIds, $search, $withTrashed, $onlyTrashed);

        return (new MergedPrivateTaskList([]))->withCustomData(compact('currentData', 'childData'));
    }

    public function addCategory(Request $request)
    {
        $me = auth()->user();

        $data = [
            'name' => $request->name,
            'type' => Category::TYPE_PRIVATE,
        ];

        if ( ! empty($request->parent_id)) {
            $parentCat = $this->categoryRepository->getItemById($request->parent_id);
            $category = $this->categoryRepository->createChild($parentCat, $data);
        } else {
            $category = $this->categoryRepository->createRoot($data);
        }
        $this->categoryRepository->addToUser($me, $category);

        return response()->result(new CategoryShortResourse($category), 'Category created');
    }

    public function moveCategory(Request $request, Category $category)
    {
        $me = auth()->user();
        $this->throwNotUserCategory($me, $category);
        if ( ! empty($request->parent_id)) {
            $parentCat = $this->categoryRepository->getItemById($request->parent_id);
            customThrowIf( ! $parentCat , 'Wrong parent category');
            $parentCat->prependNode($category);
        } else {
            $category->saveAsRoot();
        }

        return response()->result(new CategoryShortResourse($category), '');
    }

    public function updateCategoryName(Request $request, Category $category)
    {
        $me = auth()->user();
        $this->throwNotUserCategory($me, $category);
        $category = $this->categoryRepository->update($category, ['name' => $request->name]);


        return response()->result(new CategoryShortResourse($category), 'Name updated');
    }

    public function getTask(Request $request, Task $task)
    {
        $me = auth()->user();
        customThrowIf( ! $task->isAutored($me));
        $task->load(['text', 'categories']);

        return response()->result(new TaskPrivateDetailResource($task), '');
    }

    public function createDraft(Request $request)
    {
        $me = auth()->user();

        $dataTask['name'] = $request->get('name', '');
        $dataTask['due_date'] = $request->get('due_date');
        if( ! $task_id = $request->task_id) {
            $dataTask['author_id'] = $me->id;
            $dataTask['drafted_by'] = $me->id;
            $dataTask['type'] = Task::TYPE_PRIVATE;
            $task = $this->taskRepository->create($dataTask);
        } else {
            $task = $this->taskRepository->find($task_id);
            customThrowIf( ! $task->isUserDraft($me), 'It is not your draft');
            $task = $this->taskRepository->update($task,  $dataTask);
        }

        $this->taskRepository->saveText($task, $request->body);

        $catIds = $request->categories ? array_values($request->categories) : [];
        $this->taskRepository->syncCategories($task, $catIds);

        return response()->result(new TaskNewDraftResource($task));
    }

    public function createTask(Request $request)
    {
        $me = auth()->user();

        $catIds = $request->categories ? array_values($request->categories) : [];

        $task = $this->taskRepository->find($request->task_id);

        $this->throwNotUserTask($me, $task);

        $dataTask = $request->only('name', 'due_date');
        $dataTask['drafted_by'] = null;

        $task = $this->taskRepository->update($task, $dataTask);

        $this->taskRepository->saveText($task, $request->body);

        $this->taskRepository->syncCategories($task, $catIds);

        return response()->result(new TaskShortResource($task), 'Task created');
    }

    public function updateTask(Request $request, Task $task)
    {
        $me = auth()->user();

        $this->throwNotUserTask($me, $task);

        $catIds = $request->categories ? array_values($request->categories) : [];

        $dataTask = $request->only('name', 'due_date');

        $task = $this->taskRepository->update($task, $dataTask);
        $this->taskRepository->saveText($task, $request->body);

        return response()->result(new TaskShortResource($task), 'Task saved');
    }
}
