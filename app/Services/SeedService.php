<?php

namespace App\Services;

use App\User;
use App\Category;
use App\Task;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TaskRepository;
use App\Repositories\CommentRepository;

class SeedService extends CoreService
{
    protected $userRepository;
    protected $teamRepository;
    protected $categoryRepository;
    protected $taskRepository;
    protected $commentRepository;

    public function __construct(
        UserRepository $userRepository,
        TeamRepository $teamRepository,
        CategoryRepository $categoryRepository,
        TaskRepository $taskRepository,
        CommentRepository $commentRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->teamRepository = $teamRepository;
        $this->categoryRepository = $categoryRepository;
        $this->taskRepository = $taskRepository;
        $this->commentRepository = $commentRepository;
    }

    public function seed($me, $email = "")
    {
        if ($email == "") {
            $email = env('SEED_EMAIL', 'newuserseeding@treenga.com');
        }

        $user = User::where("email", $email)->first();
        if ($user) {

            $me->load('teams');

            foreach ($user->teams as $team) {
                $current_state = json_decode($team->pivot->current_state, TRUE);
                $treestate = json_decode($team->pivot->treestate, TRUE);
                $createdTeam = false;

                if (!$team->private) {

                    //Save team
                    $createdTeam = $this->createTeam($me,
                        ['name' => $team->name,
                        'username' => $team->pivot->username]
                    );

                    //Save users
                    foreach ($team->users as $team_user) {
                        if (!$team_user->pivot->is_owner) {
                            $this->addUser($team_user, $createdTeam, ['username' => $team_user->pivot->username]);
                        }
                    }
                } else {
                    foreach ($me->teams as $myTeam) {
                        if ($myTeam->private) {
                            $createdTeam = $myTeam;
                        }
                    }
                }

                if ($createdTeam) {
                    //Save category
                    $catsRelations = [];

                    foreach ($team->categories as $category) {
                        if (!($category->parent_id)) {
                            $createdCategory = $this->createCategory($me, $createdTeam,
                                ['name' => $category->name,
                                'parent_id' => $category->parent_id]
                            );

                            $catsRelations[$category->id] = $createdCategory->id;

                            if (!empty($category->children) && count($category->children) > 0) {
                                $catsRelations = $this->createCategoryChilds($me, $createdTeam, $category->children, $createdCategory->id, $catsRelations);
                            }
                        }
                    }

                    foreach ($team->tasks as $task) {

                        //Save task
                        $catIds = $task->categories->pluck('id')->toArray();
                        $realCatIds = [];
                        foreach ($catIds as $id) {
                            if (!empty($catsRelations[$id])) {
                                $realCatIds[] = $catsRelations[$id];
                            }
                        }

                        $userIds = $task->users->pluck('id')->toArray();

                        $createdTask = $this->createTask($me, $createdTeam,
                            ['name' => $task->name,
                            'due_date' => $task->due_date,
                            'drafted_by' => !empty($task->drafted_by) ? $me->id : $task->drafted_by,
                            'body' => $task->text->body,
                            'categories' => $realCatIds,
                            'users' => $userIds]
                        );

                        if (!empty($treestate['last_task']) && $treestate['last_task'] == $task->id) {
                            $treestate['last_task'] = $createdTask->id;
                        }

                        //Save comments

                        $commentsRelations = [];
                        if (!empty($task->comments) && count($task->comments) > 0) {
                            foreach ($task->comments as $comment) {
                                if (!($comment->parent_id)) {
                                    $createdComment = $this->createComment($me, $createdTeam, $createdTask,
                                        ['body' => $comment->body,
                                        'parent_id' => $comment->parent_id,
                                        'subscribers' => array_diff($createdTask->subscribers->pluck('id')->toArray(), [$user->id]),
                                        'author_id' => !empty($comment->author_id == $user->id) ? $me->id : $comment->author_id]
                                    );
                                    $commentsRelations[$comment->id] = $createdComment->id;

                                    if (!empty($comment->children) && count($comment->children) > 0) {
                                        $commentsRelations = $this->createCommentChilds($me, $user, $createdTeam, $createdTask, $comment->children, $createdComment->id, $commentsRelations);
                                    }
                                }
                            }
                        }
                    }

                    $treestate = $this->createTreestate($treestate, $catsRelations);

                    $pivotData = [
                        'treestate' => json_encode($treestate),
                    ];

                    $createdTeam = $this->teamRepository->updateUserPivot($createdTeam, $me, $pivotData);

                    foreach ($catsRelations as $oldId => $newId) {
                        if (!empty($current_state['id']) && $current_state['id'] == $oldId) {
                            $current_state['id'] = $newId;
                        }
                    }

                    $currentStateData = [
                        'current_state' => json_encode($current_state),
                    ];

                    $this->teamRepository->updateUserPivot($createdTeam, $me, $currentStateData);
                }
            }
        }
    }

    public function createTeam($me, $teamData)
    {
        $team = $this->teamRepository->itemCreate($teamData);

        $pivotData = [
            'username' => $teamData['username'],
            'is_owner' => true,
            'treestate' => $team->getDefaultTreestate(),
            'filter' => $team->getDefaultFilter(),
            'current_state' => $team->getDefaultCurrentState(),
        ];

        $team = $this->teamRepository->addUser($team, $me, $pivotData);
        $team->load('users');

        return $team;
    }

    public function addUser($user, $team, $userData)
    {
        $pivotData = [
            'username' => $userData['username'],
            'is_owner' => false,
            'treestate' => $team->getDefaultTreestate(),
            'filter' => $team->getDefaultFilter(),
            'current_state' => $team->getDefaultCurrentState(),
        ];

        $team = $this->teamRepository->addUser($team, $user, $pivotData);
    }

    public function createCategory($me, $team, $categoryData)
    {
        $me->load('teams');

        $data = [
            'name' => $categoryData['name'],
            'type' => Category::TYPE_PUBLIC,
        ];

        if ( ! empty($categoryData['parent_id'])) {
            $parentCat = $this->categoryRepository->getItemById($categoryData['parent_id']);
            customThrowIf( ! $parentCat , 'Wrong parent category');

            $category = $this->categoryRepository->createChild($parentCat, $data);
        } else {
            $category = $this->categoryRepository->createRoot($data);
        }
        $this->categoryRepository->addToTeam($team, $category);
        $this->categoryRepository->subscribeUser($category, [$me->id]);

        return $category;
    }

    public function createCategoryChilds($me, $team, $categories, $parent_id, $catsRelations)
    {
        foreach ($categories as $category) {
            $createdCategory = $this->createCategory($me, $team,
                ['name' => $category->name,
                'parent_id' => $parent_id]
            );

            $catsRelations[$category->id] = $createdCategory->id;

            if (!empty($category->children) && count($category->children) > 0) {
                $catsRelations = $this->createCategoriesChilds($me, $team, $category->children, $createdCategory->id, $catsRelations);
            }
        }

        return $catsRelations;

    }

    public function createTreestate($treestate, $catsRelations) {
        $newTreestate = $treestate;
        foreach ($catsRelations as $oldId => $id) {
            if (!empty($treestate['edit']['assignees_tree'])) {
                foreach ($treestate['edit']['assignees_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['edit']['assignees_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['edit']['private_tree'])) {
                foreach ($treestate['edit']['private_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['edit']['private_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['edit']['public_tree'])) {
                foreach ($treestate['edit']['public_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['edit']['public_tree'][$k] = $id;
                    }
                }
            }


            if (!empty($treestate['main']['assignees_tree'])) {
                foreach ($treestate['main']['assignees_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['main']['assignees_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['main']['private_tree'])) {
                foreach ($treestate['main']['private_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['main']['private_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['main']['public_tree'])) {
                foreach ($treestate['main']['public_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['main']['public_tree'][$k] = $id;
                    }
                }
            }
            
            if (!empty($treestate['main']['system_tree'])) {
                foreach ($treestate['main']['system_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['main']['system_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['main']['authors_tree'])) {
                foreach ($treestate['main']['authors_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['main']['authors_tree'][$k] = $id;
                    }
                }
            }


            if (!empty($treestate['create']['assignees_tree'])) {
                foreach ($treestate['create']['assignees_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['create']['assignees_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['create']['private_tree'])) {
                foreach ($treestate['create']['private_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['create']['private_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['create']['public_tree'])) {
                foreach ($treestate['create']['public_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['create']['public_tree'][$k] = $id;
                    }
                }
            }


            if (!empty($treestate['filter']['assignees_tree'])) {
                foreach ($treestate['filter']['assignees_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['filter']['assignees_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['filter']['private_tree'])) {
                foreach ($treestate['filter']['private_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['filter']['private_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['filter']['public_tree'])) {
                foreach ($treestate['filter']['public_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['filter']['public_tree'][$k] = $id;
                    }
                }
            }

            if (!empty($treestate['filter']['authors_tree'])) {
                foreach ($treestate['filter']['authors_tree'] as $k => $val) {
                    if ($val == $oldId) {
                        $newTreestate['filter']['authors_tree'][$k] = $id;
                    }
                }
            }
        }

        return $newTreestate;
    }

    public function createTask($me, $team, $taskData)
    {
        $team->load('users');

        $dataTask['name'] = $taskData['name'];
        $dataTask['due_date'] = $taskData['due_date'];
        $dataTask['drafted_by'] = $taskData['drafted_by'];
        $dataTask['team_id'] = $team->id;
        $dataTask['author_id'] = $me->id;
        $dataTask['owner_id'] = $me->id;
        $dataTask['type'] = $team->private ? Task::TYPE_PRIVATE : Task::TYPE_PUBLIC;
        $task = $this->taskRepository->create($dataTask);

        $this->taskRepository->saveText($task, $taskData['body']);
        $this->taskRepository->syncUsers($task, $taskData['users']);
        $this->taskRepository->syncCategories($task, $taskData['categories']);

        return $task;
    }

    public function createComment($me, $team, $task, $commentData)
    {
        $dataComment['body'] = $commentData['body'];
        $dataComment['author_id'] = $commentData['author_id'];
        $dataComment['username'] = $team->getUsername($me);

        if($commentData['parent_id']) {
            $parent = $this->commentRepository->find($commentData['parent_id']);
            customThrowIf( ! $parent, 'Wrong parent comment');
            $comment = $this->commentRepository->createTasksChild($task, $parent, $dataComment);
        } else {
            $comment = $this->commentRepository->createTasksRoot($task, $dataComment);
        }
        $me->readComments()->attach($comment->id);

        $subscribe['subscribers'] = $this->taskRepository->subscribeUser($task, $commentData['subscribers'], Task::SUBSCRIBE_TYPE_COMMENT_MENTION);
        $subscribe['author'] = $this->taskRepository->subscribeUser($task, [$me->id], Task::SUBSCRIBE_TYPE_COMMENT_AUTHOR);

        return $comment;
    }

    public function createCommentChilds($me, $user, $team, $task, $comments, $parent_id, $commentsRelations)
    {
        foreach ($comments as $comment) {
            $createdComment = $this->createComment($me, $team, $task,
                ['body' => $comment->body,
                'parent_id' => $parent_id,
                'subscribers' => array_diff($task->subscribers->pluck('id')->toArray(), [$user->id]),
                'author_id' => !empty($comment->author_id == $user->id) ? $me->id : $comment->author_id]
            );

            $commentsRelations[$comment->id] = $createdComment->id;

            if (!empty($comment->children) && count($comment->children) > 0) {
                $commentsRelations = $this->createCommentChilds($me, $user, $team, $task, $comment->children, $createdComment->id, $commentsRelations);
            }
        }

        return $commentsRelations;

    }
}
