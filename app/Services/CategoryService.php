<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\User;
use App\Team;
use App\Category;
use App\History;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Http\Resources\Category\Short as CategoryShortResourse;
use App\Http\Resources\Category\Detail as CategoryDetailResourse;
use App\Http\Resources\Category\MergedTaskList as CategoryMergedTaskListResourse;
use App\Http\Resources\Category\WithHistory as CategoryWithHistoryResourse;
use App\Http\Resources\Comment\Short as CommentShortResource;
use App\Events\Category\Created as CategoryCreatedEvent;
use App\Events\Category\Moved as CategoryMovedEvent;
use App\Events\Category\Renamed as CategoryRenamedEvent;
use App\Events\Category\Deleted as CategoryDeletedEvent;
use App\Events\Category\DescCreated as CategoryDescCreatedEvent;
use App\Events\Category\DescEdited as CategoryDescEditedEvent;
use App\Events\Category\Commented as CategoryCommentedEvent;
use App\Events\Category\Viewed as CategoryViewedEvent;
use App\Events\Category\Reverted as CategoryRevertedEvent;
use App\Events\Team\GetTasks as TeamGetTasksEvent;

class CategoryService extends CoreService
{
    protected $categoryRepository;
    protected $commentRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        CommentRepository $commentRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->commentRepository = $commentRepository;
    }

    //TODO
    public function addPrivateCategory(Request $request, Team $team)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $data = [
            'name' => $request->name,
            'type' => Category::TYPE_PRIVATE,
        ];

        if ( ! empty($request->parent_id)) {
            $parentCat = $this->categoryRepository->getItemById($request->parent_id);
            customThrowIf( ! $parentCat , 'Wrong parent category');
            $this->throwCategoryTeam($parentCat, $team);
            $category = $this->categoryRepository->createChild($parentCat, $data);
        } else {
            $category = $this->categoryRepository->createRoot($data);
        }
        $this->categoryRepository->addToTeam($team, $category);
        $this->categoryRepository->addToUser($me, $category);

        return response()->result(new CategoryShortResourse($category), 'Category created');
    }

    public function addPublicCategory(Request $request, Team $team)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserLock($me);
        $this->throwUserTeam($me, $team);

        $data = [
            'name' => $request->name,
            'type' => Category::TYPE_PUBLIC,
        ];

        if ( ! empty($request->parent_id)) {
            $parentCat = $this->categoryRepository->getItemById($request->parent_id);
            customThrowIf( ! $parentCat , 'Wrong parent category');
            $this->throwCategoryTeam($parentCat, $team);
            $category = $this->categoryRepository->createChild($parentCat, $data);
        } else {
            $category = $this->categoryRepository->createRoot($data);
        }
        $this->categoryRepository->addToTeam($team, $category);

        $this->categoryRepository->subscribeUser($category, [$me->id]);

        event(new CategoryCreatedEvent($category, $team));

        return response()->result(new CategoryShortResourse($category), 'Category created');
    }

    public function getItem(Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $category = $this->categoryRepository->loadDetailInfo($category, $team);

        $category->comments = $category->comments->toTree();

        event(new CategoryViewedEvent($team, $category, $me));

        return response()->result(new CategoryDetailResourse($category));
    }

    public function getItemHistory(Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $category = $this->categoryRepository->loadHistoryInfo($category);

        return response()->result(new CategoryWithHistoryResourse($category));
    }

    public function setItemHistory(Team $team, Category $category, History $history)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        customThrowIf( ! $category->histories->contains('id', $history->id), 'Incorrect revision');

        $this->categoryRepository->addCategoryHistory($category, $me);

        $this->categoryRepository->setHistory($category, $history);

        $category = $this->categoryRepository->loadHistoryInfo($category);

        event(new CategoryRevertedEvent($team, $category, $me));

        return response()->result(new CategoryWithHistoryResourse($category), 'Task reverted');
    }

    public function move(Request $request, Team $team, Category $category)
    {
        $me = auth()->user();
        $this->throwUserTeam($me, $team);

        if ( ! empty($request->parent_id)) {
            $parentCat = $this->categoryRepository->getItemById($request->parent_id);
            customThrowIf( ! $parentCat , 'Wrong parent category');
            $this->throwCategoryTeam($parentCat, $team);
            $parentCat->prependNode($category);

        } else {
            $category->saveAsRoot();
        }

        event(new CategoryMovedEvent($category, $team));

        return response()->result(new CategoryShortResourse($category), '');
    }

    public function addDesc(Request $request, Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $subscribers = $request->subscribers;
        $subscribersIds = $subscribers ? array_values($subscribers) : [];
        $this->throwTeamHasUsersIds($team, $subscribersIds);

        customThrowIf( ! empty($category->text), 'Description exists');

        $category = $this->categoryRepository->saveText($category, $request->body);

        $category->load('text');

        $sync = $this->categoryRepository->subscribeUser($category, $subscribersIds);

        event(new CategoryDescCreatedEvent($team, $category, $me));

        return response()->result(new CategoryDetailResourse($category));
    }

    public function updateDesc(Request $request, Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        customThrowIf( empty($category->text), 'Description doesn\'t exist');

        $subscribers = $request->subscribers;
        $subscribersIds = $subscribers ? array_values($subscribers) : [];
        $this->throwTeamHasUsersIds($team, $subscribersIds);

        $change = false;
        if(optional($category->text)->body !== $request->body) {
            $this->categoryRepository->addCategoryHistory($category, $me);
            $category = $this->categoryRepository->saveText($category, $request->body);
            $change = true;
        }

        $sync = $this->categoryRepository->subscribeUser($category, $subscribersIds);
        $newSubscribersIds = $sync['attached'];

        $category->load('text');

        if($change) {
            event(new CategoryDescEditedEvent($team, $category, $me, $newSubscribersIds));
        }

        return response()->result(new CategoryDetailResourse($category));
    }

    public function updateName(Request $request, Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $category = $this->categoryRepository->update($category, ['name' => $request->name]);

        $category->load('text');

        event(new CategoryRenamedEvent($category, $team));

        return response()->result(new CategoryDetailResourse($category), 'Name updated');
    }

    public function addComment(Request $request, Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $subscribers = $request->get('subscribers', []);
        $subscribersIds = $subscribers ? array_values($subscribers) : [];

        $this->throwTeamHasUsersIds($team, $subscribersIds);

        $dataComment = $request->only('body');
        $dataComment['author_id'] = $me->id;
        $dataComment['username'] = $team->getUsername($me);

        if($request->parent_id) {
            $parent = $this->commentRepository->find($request->parent_id);

            customThrowIf( ! $parent || ! $category->comments->contains('id', $parent->id), 'Wrong parent comment');

            $comment = $this->commentRepository->createCategoriesChild($category, $parent, $dataComment);
        } else {
            $comment = $this->commentRepository->createCategoriesRoot($category, $dataComment);
        }

        $this->categoryRepository->subscribeUser($category, $subscribersIds);

        event(new CategoryCommentedEvent($team, $category, $me));

        return response()->result(new CommentShortResource($comment), 'Comment added');
    }

    public function deleteItem(Team $team, Category $category)
    {
        $me = auth()->user();

        $me->load('teams');

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $this->categoryRepository->forceDeleteWithRelations($category);

        event(new CategoryDeletedEvent($category, $team));

        return response()->result(true, 'Category deleted');
    }

    public function getTasks(Team $team, Category $category)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        $withTrashed = request()->withTrashed;
        $onlyTrashed = request()->onlyTrashed;

        $search = $this->getSearchParam();

        $currentData = $this->categoryRepository->getCurrentTasks($category, $team, $me, $search, $withTrashed, $onlyTrashed);

        $currentTaskIds = array_get($currentData, 'tasks')->pluck('id')->toArray();

        $childData = $this->categoryRepository->getChildTasks($category, $team, $me, $currentTaskIds, $search, $withTrashed, $onlyTrashed);

        $category_id = $category->id;

        event(new TeamGetTasksEvent($team, $me, 'category', $onlyTrashed, $category->id));

        return (new CategoryMergedTaskListResourse([]))->withCustomData(compact('currentData', 'childData', 'category_id'));
    }

    public function subscribe(Team $team, Category $category)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        customThrowIf( $category->subscribers->contains('id', $me->id), 'You already subscribed to this task');

        $this->categoryRepository->subscribeUser($category, [$me->id]);

        return response()->result(true, 'Subscribed');
    }

    public function unsubscribe(Team $team, Category $category)
    {
        $me = auth()->user();

        $this->throwUserTeam($me, $team);

        $this->throwCategoryTeam($category, $team);

        customThrowIf( ! $category->subscribers->contains('id', $me->id), 'You not subscribed to this task');

        $this->categoryRepository->unsubscribeUser($category, [$me->id]);

        return response()->result(true, 'Unsubscribed');
    }
}
