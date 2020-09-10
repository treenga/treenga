<?php

namespace App\Repositories;

use App\Task;
use App\Comment;
use App\Category;

class CommentRepository
{
    public function find(int $id)
    {
        return Comment::find($id);
    }

    public function createTasksRoot(Task $task, array $data)
    {
        $comment = new Comment($data);
        $comment->makeRoot();
        $task->comments()->save($comment);
        return $comment;
    }

    public function createTasksChild(Task $task, Comment $parent, array $data)
    {
        $comment = new Comment($data);
        $comment->appendToNode($parent);
        $task->comments()->save($comment);
        return $comment;
    }

    public function createCategoriesRoot(Category $category, array $data)
    {
        $comment = new Comment($data);
        $comment->makeRoot();
        $category->comments()->save($comment);
        return $comment;
    }

    public function createCategoriesChild(Category $category, Comment $parent, array $data)
    {
        $comment = new Comment($data);
        $comment->appendToNode($parent);
        $category->comments()->save($comment);
        return $comment;
    }

}
