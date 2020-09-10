<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CategoryService;
use App\Team;
use App\Category;
use App\Http\Requests\Category\Save as CategorySaveRequest;
use App\Http\Requests\Category\SaveText as CategorySaveTextRequest;
use App\Http\Requests\Category\SaveName as CategorySaveNameRequest;
use App\Http\Requests\Category\Move as CategoryMoveRequest;
use App\Http\Requests\Comment\Create as CommentCreateRequest;
use App\History;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function addPrivateCategory(CategorySaveRequest $request, Team $team)
    {
        return $this->categoryService->addPrivateCategory($request, $team);
    }

    public function addPublicCategory(CategorySaveRequest $request, Team $team)
    {
        return $this->categoryService->addPublicCategory($request, $team);
    }

    public function getCategories(Team $team)
    {
        return $this->categoryService->getCategories($team);
    }

    public function getItem(Team $team, Category $category)
    {
        return $this->categoryService->getItem($team, $category);
    }

    public function getItemHistory(Team $team, Category $category)
    {
        return $this->categoryService->getItemHistory($team, $category);
    }

    public function setItemHistory(Team $team, Category $category, History $history)
    {
        return $this->categoryService->setItemHistory($team, $category, $history);
    }

    public function move(CategoryMoveRequest $request, Team $team, Category $category)
    {
        return $this->categoryService->move($request, $team, $category);
    }

    public function addDesc(CategorySaveTextRequest $request, Team $team, Category $category)
    {
        return $this->categoryService->addDesc($request, $team, $category);
    }

    public function addComment(CommentCreateRequest $request, Team $team, Category $category)
    {
        return $this->categoryService->addComment($request, $team, $category);
    }

    public function updateDesc(CategorySaveTextRequest $request, Team $team, Category $category)
    {
        return $this->categoryService->updateDesc($request, $team, $category);
    }

    public function updateName(CategorySaveNameRequest $request, Team $team, Category $category)
    {
        return $this->categoryService->updateName($request, $team, $category);
    }

    public function deleteItem(Team $team, Category $category)
    {
        return $this->categoryService->deleteItem($team, $category);
    }

    public function getTasks(Team $team, Category $category)
    {
        return $this->categoryService->getTasks($team, $category);
    }

    public function subscribe(Team $team, Category $category)
    {
        return $this->categoryService->subscribe($team, $category);
    }

    public function unsubscribe(Team $team, Category $category)
    {
        return $this->categoryService->unsubscribe($team, $category);
    }
}
