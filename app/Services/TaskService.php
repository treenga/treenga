<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\User;
use App\Team;
use App\Category;
use App\Task;
use App\History;
use App\Repositories\TaskRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\UserRepository;
use App\Http\Resources\Task\Short as TaskShortResource;
use App\Http\Resources\Task\Detail as TaskDetailResource;
use App\Http\Resources\Task\WithHistory as TaskWithHistoryResource;
use App\Http\Resources\Comment\Short as CommentShortResource;
use App\Http\Resources\Task\ListItem as TaskListItemResource;
use App\Http\Resources\Task\Attributes as TaskAttributesResource;
use App\Http\Resources\Task\NewDraft as TaskNewDraftResource;
use App\Events\Task\Created as TaskCreatedEvent;
use App\Events\Task\Edited as TaskEditedEvent;
use App\Events\Task\EditedText as TaskEditedTextEvent;
use App\Events\Task\Commented as TaskCommentedEvent;
use App\Events\Task\Viewed as TaskViewedEvent;
use App\Events\Task\Deleted as TaskDeletedEvent;
use App\Events\Task\Closed as TaskClosedEvent;
use App\Events\Task\Restored as TaskRestoredEvent;
use App\Events\Task\RevertedHistory as TaskRevertedHistoryEvent;
use App\Events\Task\NewSubscriber as TaskNewSubscriberEvent;
use App\Events\Task\DeleteSubscriber as TaskDeleteSubscriberEvent;

class TaskService extends CoreService
{
    protected $taskRepository;
    protected $categoryRepository;
    protected $commentRepository;
    protected $userRepository;

    public function __construct(
        TaskRepository $taskRepository,
        CategoryRepository $categoryRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository
    )
    {
        $this->taskRepository = $taskRepository;
        $this->categoryRepository = $categoryRepository;
        $this->commentRepository = $commentRepository;
        $this->userRepository = $userRepository;
    }

    public function publicUnsubscribe(string $hashString)
    {
        $hash = $this->taskRepository->getSubcribedHash($hashString);
        customThrowIf( ! $hash, 'Wrong link');
        $user = $hash->users->first();
        $task = $hash->tasks->first();

        $task = $this->taskRepository->unsubscribeUser($task, [$user->id]);
        $hash->delete();

        event(new TaskDeleteSubscriberEvent($task, $user));

        return response()->result(true, 'You unsubscribed from task');
    }

    public function createDraft(Request $request, Team $team)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        $team->load('users');

        $dataTask['name'] = $request->get('name', '');
        $dataTask['team_id'] = $team->id;
        $dataTask['author_id'] = $me->id;
        $dataTask['drafted_by'] = $me->id;
        $dataTask['due_date'] = $request->get('due_date');

        $task_id = $request->task_id;
        //TODO
        if( ! $task_id) {
            $dataTask['type'] = $team->private ? Task::TYPE_PRIVATE : Task::TYPE_PUBLIC;
            $task = $this->taskRepository->create($dataTask);
        } else {
            $task = $this->taskRepository->find($task_id);
            customThrowIf( ! $task->isUserDraft($me), 'It is not your draft');
            $task = $this->taskRepository->update($task,  $dataTask);
        }

        $this->taskRepository->saveText($task, $request->body);

        $userIds = $request->users ? array_values($request->users) : [];
        $this->taskRepository->syncUsers($task, $userIds);
        $catIds = $request->categories ? array_values($request->categories) : [];
        $this->taskRepository->syncCategories($task, $catIds);

        return response()->result(new TaskNewDraftResource($task));
    }

    //TODO
    public function createPrivateTask(Request $request, Team $team)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        $team->load('users');

        $catIds = $request->categories ? array_values($request->categories) : [];
        $this->throwTeamHasCats($team, $catIds);

        $task = $this->taskRepository->find($request->task_id);

        customThrowIf($task->isPublic(), 'It is public task');
        customThrowIf( ! $task->isUserDraft($me), 'It is not your draft');

        $dataTask = $request->only('name', 'due_date');
        $dataTask['team_id'] = $team->id;
        $dataTask['author_id'] = $me->id;
        $dataTask['drafted_by'] = null;

        $task = $this->taskRepository->update($task, $dataTask);

        $this->taskRepository->saveText($task, $request->body);

        $this->taskRepository->syncCategories($task, $catIds);

        return response()->result(new TaskShortResource($task), 'Task created');
    }

    public function createPublicTask(Request $request, Team $team)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        $team->load('users');

        $userIds = $request->users ? array_values($request->users) : [];
        $this->throwTeamHasUsersIds($team, $userIds);

        $catIds = $request->categories ? array_values($request->categories) : [];
        $this->throwTeamHasCats($team, $catIds);

        $task = $this->taskRepository->find($request->task_id);

        customThrowIf( ! $task, 'Wrong task');
        customThrowIf( ! $task->isUserDraft($me), 'It is not your draft');

        $dataTask = $request->only('name', 'due_date');
        $dataTask['team_id'] = $team->id;
        $dataTask['author_id'] = $me->id;
        $dataTask['owner_id'] = $me->id;
        $dataTask['type'] = $team->private ? Task::TYPE_PRIVATE : Task::TYPE_PUBLIC;
        $dataTask['drafted_by'] = null;

        $task = $this->taskRepository->update($task, $dataTask);

        $this->taskRepository->saveText($task, $request->body);

        $this->taskRepository->syncUsers($task, $userIds);

        $this->taskRepository->syncCategories($task, $catIds);

        $subscribers = $request->get('subscribers', []);
        $subscribersIds = $subscribers ? array_values($subscribers) : [];
        //Subcribe
        $subscribe['author'] = $this->taskRepository->subscribeUser($task, [$me->id], Task::SUBSCRIBE_TYPE_AUTHOR);

        $subscribe['users'] = $this->taskRepository->subscribeUser($task, array_diff($userIds, [$me->id]), Task::SUBSCRIBE_TYPE_ASSIGN);

        $subscribersIds = array_diff($subscribersIds, array_merge($userIds, [$me->id]));
        $subscribe['subscribers'] = $this->taskRepository->subscribeUser($task, $subscribersIds, Task::SUBSCRIBE_TYPE_MENTION);

        event(new TaskCreatedEvent($task, $team, $me, $subscribe));

        return response()->result(new TaskShortResource($task), 'Task created');
    }

    public function getItem(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);
        $this->throwNotUserTask($me, $task);

        $task = $this->taskRepository->loadDetailInfo($task, $team, $me);

        $userReadComments = $this->userRepository->getReadTaskComments($me, $task);
        $userReadCommentIds = $userReadComments->pluck('id')->toArray();
        $task->comments->each(function($value) use($userReadCommentIds){
            $value->is_new = ! in_array($value->id, $userReadCommentIds);
        });
        $task->activities->each(function($value) use($me){
            $value->is_new = ($value->unreadUsers()->where('user_id', $me->id)->count() > 0);
            $me->unreadActivities()->detach($value->id);
        });
        $task->allActivities = collect([$task->comments->toTree(), $task->activities])->collapse()->sortBy('created_at');

        event(new TaskViewedEvent($task, $team, $me));

        return response()->result(new TaskDetailResource($task), '');
    }

    public function saveCommentsstate(Team $team, Task $task, Request $request)
    {
        $me = auth()->user();
        $pivotData = [
            'commentsstate' => json_encode($request->all()),
        ];
        $this->taskRepository->saveTaskUserOptions($task, $me, $pivotData);

        return response()->result(true);
    }

    public function deleteItem(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);
        $this->throwNotUserTask($me, $task);

        $task = $this->deleteTask($team, $task, $me);

        event(new TaskClosedEvent($task, $team, $me));

        return response()->result(true, 'Task closed');
    }

    private function deleteTask(Team $team, Task $task, User $me)
    {
        $task = $this->taskRepository->delete($task);
        $this->taskRepository->deleteNotify($task);

        event(new TaskDeletedEvent($task, $team, $me));

        return $task;
    }

    public function restoreItem(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);
        $this->throwNotUserTask($me, $task);
        customThrowIf( ! $task->is_archived, 'Task not archived');

        $task = $this->restoreTask($team, $task, $me);

        return response()->result(true, 'Task opened');
    }

    private function restoreTask(Team $team, Task $task, User $me)
    {
        $task->restore();
        event(new TaskRestoredEvent($task, $team, $me));

        return response()->result(new TaskAttributesResource($task), 'Task reopened');
    }

    public function deleteDraftForce(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);
        $this->throwTaskTeam($task, $team);
        $this->throwNotUserTask($me, $task);

        customThrowIf( ! $task->isUserDraft($me), 'It is not your draft');

        $task = $this->taskRepository->deleteAndDetachAll($task);
        $task = $this->taskRepository->forceDelete($task);

        return response()->result(true, 'Draft deleted');
    }

    public function getAttributes(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);

        $task->load('categories', 'users');

        return response()->result(new TaskAttributesResource($task), '');
    }

    public function setAttributes(Request $request, Team $team, Task $task)
    {
        $me = auth()->user();

        $team->load('users', 'categories');

        $users = $request->get('users');
        $userIds = $users ? array_values($users) : [];
        $this->throwTeamHasUsersIds($team, $userIds);

        $catIds = $request->categories ? array_values($request->categories) : [];
        $this->throwTeamHasCats($team, $catIds);

        $task = $this->setTaskAttributes($team, $task, $me, $userIds, $catIds);

        return response()->result(new TaskAttributesResource($task), 'Task saved');
    }

    public function setMass(Request $request, Team $team)
    {
        $me = auth()->user();
        $this->throwUserTeam($me, $team);
        $tasks = $request->get('tasks');
        $taskIds = $tasks ? array_values($tasks) : [];
        $this->throwTeamHasTasks($team, $taskIds, $withTrashed = true);
        $action = $request->action;
        $tasks = $team->tasks->whereIn('id', $taskIds);
        if(in_array($action, [Task::MASS_ACTION_ASSIGN, Task::MASS_ACTION_UNASSIGN])) {
            $userIds =  $request->users ? array_values($request->users) : [];
            $this->throwTeamHasUsersIds($team, $userIds);
            $catIds = $request->categories ? array_values($request->categories) : [];
            $this->throwTeamHasCats($team, $catIds);
            switch ($action) {
                case Task::MASS_ACTION_ASSIGN:
                    foreach($tasks as $task) {
                        $sync['categories'] = $task->categories()->syncWithoutDetaching($catIds);
                        $sync['users'] = $task->users()->syncWithoutDetaching($userIds);
                        $subscribe['users'] = $this->taskRepository->subscribeUser($task, $userIds, Task::SUBSCRIBE_TYPE_ASSIGN);
                        event(new TaskEditedEvent($task, $team, $me, $sync, $subscribe, $task->getOriginal()));
                    }
                    return response()->result(true);
                    break;
                case Task::MASS_ACTION_UNASSIGN:
                    foreach($tasks as $task) {
                        $detachCats = $task->categories()->detach($catIds);
                        $detachUsers = $task->users()->detach($userIds);
                        $sync['categories'] = $sync['users'] = [
                            'attached' => [],
                            'detached' => $detachCats ? $catIds : [],
                            'updated' => [],
                        ];
                        $sync['users'] = [
                            'attached' => [],
                            'detached' => $detachUsers ? $userIds : [],
                            'updated' => [],
                        ];
                        event(new TaskEditedEvent($task, $team, $me, $sync, $subscribe = [], $task->getOriginal()));
                    }
                    return response()->result(true);
                    break;
            }
        } else {
            switch ($action) {
                case Task::MASS_ACTION_OPEN:
                    foreach($tasks as $task) {
                        if($task->isClosed()) {
                            $task->restore();
                            event(new TaskRestoredEvent($task, $team, $me));
                        }
                    }
                    return response()->result(true);
                    break;
                case Task::MASS_ACTION_CLOSE:
                    foreach($tasks as $task) {
                        if($task->isOpened()) {
                            $task = $this->taskRepository->delete($task);
                            $this->taskRepository->deleteNotify($task);
                            event(new TaskDeletedEvent($task, $team, $me));
                        }
                    }
                    return response()->result(true);
                    break;
                case Task::MASS_ACTION_SET_DUE_DATE:
                    foreach($tasks as $task) {
                        $task = $this->taskRepository->update($task, ['due_date' => $request->due_date]);
                    }
                    return response()->result(true);
                    break;
            }
        }
    }

    private function setTaskAttributes(Team $team, Task $task, User $me, array $userIds, array $catIds)
    {
        $sync['categories'] = $this->taskRepository->syncCategories($task, $catIds);

        $newCatsIds = $sync['categories']['attached'];
        if(count($newCatsIds)) {
            $task->load('categories');
        }

        $subscribe['users'] = $this->taskRepository->subscribeUser($task, $userIds, Task::SUBSCRIBE_TYPE_ASSIGN);
        $sync['users'] = $this->taskRepository->syncUsers($task, $userIds);
        $newUsersIds = $sync['users']['attached'];

        event(new TaskEditedEvent($task, $team, $me, $sync, $subscribe, $task->getOriginal()));

        return $task;
    }

    public function getItemHistory(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);

        $task = $this->taskRepository->loadHistoryInfo($task, $team);

        return response()->result(new TaskWithHistoryResource($task), '');
    }

    public function setItemHistory(Team $team, Task $task, History $history)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);

        customThrowIf( ! $task->histories->contains('id', $history->id), 'Incorrect revision');

        $this->taskRepository->addHistory($task);
        $this->taskRepository->setHistory($task, $history);
        $task = $this->taskRepository->loadHistoryInfo($task, $team);

        $subscribe['author'] = $this->taskRepository->subscribeUser($task, [$me->id], Task::SUBSCRIBE_TYPE_REVERT_HISTORY);

        event(new TaskRevertedHistoryEvent($task, $team, $me, $subscribe));

        return response()->result(new TaskWithHistoryResource($task), 'Task reverted');
    }

    public function updateTask(Request $request, Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        customThrowIf($task->isDraft(), 'It is draft');

        $team->load('users');

        $users = $request->get('users', []);
        $userIds = $users ? array_values($users) : [];
        $this->throwTeamHasUsersIds($team, $userIds);

        $subscribers = $request->get('subscribers', []);
        $subscribersIds = $subscribers ? array_values($subscribers) : [];

        $this->throwTeamHasUsersIds($team, $subscribersIds);

        $catIds = $request->categories ? array_values($request->categories) : [];
        $this->throwTeamHasCats($team, $catIds);

        $changes = false;
        $changesText = false;
        if ( ! empty($request->body) && $task->text->body !== $request->body) {
            $this->taskRepository->addHistory($task);
            $changesText = true;
        }

        $dataTask = $request->only('name', 'due_date');
        $dataTask['team_id'] = $team->id;

        $taskData = $this->taskRepository->updateWithOriginal($task, $dataTask);
        $task = $taskData['task'];
        $original = $taskData['original'];
        $changes = ! $changes ? $task->wasChanged() : true;
        $this->taskRepository->saveText($task, $request->body);
        $this->taskRepository->forceDeleteTempUserItem($task, $me);

        $sync['users'] = $this->taskRepository->syncUsers($task, $userIds);
        $sync['categories'] = $this->taskRepository->syncCategories($task, $catIds);
        //Subcribe
        $subscribe['author'] = $this->taskRepository->subscribeUser($task, [$me->id], Task::SUBSCRIBE_TYPE_EDITOR);
        $subscribe['users'] = $this->taskRepository->subscribeUser($task, array_diff($userIds, [$me->id]), Task::SUBSCRIBE_TYPE_ASSIGN);

        $subscribersIds = array_diff($subscribersIds, array_merge($userIds, [$me->id]));
        $subscribe['subscribers'] = $this->taskRepository->subscribeUser($task, $subscribersIds, Task::SUBSCRIBE_TYPE_MENTION);

        event(new TaskEditedEvent($task, $team, $me, $sync, $subscribe, $original, $changesText));

        return response()->result(new TaskShortResource($task), 'Task saved');
    }

    public function autosaveTask(Request $request, Team $team, Task $task)
    {
        $me = auth()->user();
        customThrowIf(($task->isDraft() && $task->drafted_by != $me->id), 'Wrong task');
        $data = $request->validated();
        $this->taskRepository->saveTempUserItem($task, $me, $data);
        return response()->result(true);
    }

    public function deleteAutosaveTask(Request $request, Team $team, Task $task)
    {
        $me = auth()->user();
        $this->taskRepository->deleteTempUserItem($task, $me);
        return response()->result(true);
    }

    public function restoreAutosaveTask(Request $request, Team $team, Task $task)
    {
        $me = auth()->user();
        $this->taskRepository->restoreTempUserItem($task, $me);
        return response()->result(true);
    }

    public function createComment(Request $request, Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);

        $subscribers = $request->get('subscribers', []);
        $subscribersIds = $subscribers ? array_values($subscribers) : [];

        $this->throwTeamHasUsersIds($team, $subscribersIds);

        customThrowIf($task->isDraft(), 'It is draft');

        $dataComment = $request->only('body');
        $dataComment['author_id'] = $me->id;
        $dataComment['username'] = $team->getUsername($me);

        if($request->parent_id) {
            $parent = $this->commentRepository->find($request->parent_id);

            customThrowIf( ! $parent || ! $task->comments->contains('id', $parent->id), 'Wrong parent comment');

            $comment = $this->commentRepository->createTasksChild($task, $parent, $dataComment);
        } else {
            $comment = $this->commentRepository->createTasksRoot($task, $dataComment);
        }
        $me->readComments()->attach($comment->id);

        $subscribe['subscribers'] = $this->taskRepository->subscribeUser($task, $subscribersIds, Task::SUBSCRIBE_TYPE_COMMENT_MENTION);
        $subscribe['author'] = $this->taskRepository->subscribeUser($task, [$me->id], Task::SUBSCRIBE_TYPE_COMMENT_AUTHOR);

        count($subscribersIds) ? $task->load('subscribers') : false;
        $comment->load('author');
        event(new TaskCommentedEvent($comment, $team, $task, $me, $subscribe));

        return response()->result(new CommentShortResource($comment), 'Comment added');
    }

    public function subscribe(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserLock($me);

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);

        customThrowIf( $task->subscribers->contains('id', $me->id), 'You already subscribed to this task');

        $this->taskRepository->subscribeUser($task, [$me->id], Task::SUBSCRIBE_TYPE_HAND);
        $this->taskRepository->addUnsubscribeHashByUserId($task, $me->id);

        return response()->result(true, 'Subscribed');
    }

    public function unsubscribe(Team $team, Task $task)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwTaskTeam($task, $team);

        customThrowIf( ! $task->subscribers->contains('id', $me->id), 'You not subscribed to this task');

        $task = $this->taskRepository->unsubscribeUser($task, [$me->id]);
        $this->taskRepository->deleteSubscribeHash($task, $me);

        event(new TaskDeleteSubscriberEvent($task, $me));

        return response()->result(true, 'Unsubscribed');
    }

    public function search(Request $request, Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $tasks = $this->taskRepository->searchTasks($team, $me, $request);

        return response()->result(TaskListItemResource::collection($tasks));
    }
}
