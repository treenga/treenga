<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\User;
use App\Team;
use App\Task;
use App\History;
use App\Hash;

class TaskRepository
{
    public function find(int $id)
    {
        return Task::find($id);
    }

    public function create(array $data)
    {
        $task = new Task();
        $task->fill($data);
        $task->save();
        return $task;
    }

    public function update(Task $task, array $data)
    {
        $task->fill($data);
        $task->save();
        return $task;
    }

    public function updateWithOriginal(Task $task, array $data)
    {
        $task->fill($data);
        $original = $task->getOriginal();
        if ($task->attributesToArray() != $original) {
            $task->save();
        }
        return compact('task', 'original');
    }

    public function updateOrCreate($id = null, array $data)
    {
        $task = Task::firstOrNew(['id' => $id]);
        $task->fill($data);
        $task->save();
        return $task;
    }

    public function delete(Task $task)
    {
        $task->delete();
        return $task;
    }

    public function forceDelete(Task $task)
    {
        $task->forceDelete();
        return $task;
    }

    public function deleteNotify(Task $task)
    {
        $task->userNotifications->each(function($value){
            $value->delete();
        });
        return $task;
    }

    public function detachCategories(Task $task)
    {
        $task->categories()->detach();
        return $task;
    }

    public function deleteAndDetachAll($task)
    {
        $task->userNotifications->each(function($value){
            $value->delete();
        });
        $task->users()->detach();
        $task->categories()->detach();
        $task->subscribers()->detach();
        $task->userOptions()->detach();
        $task->text()->delete();
        $task->histories->each(function($value){
            $value->delete();
        });
        $task->comments->each(function($value){
            $value->delete();
        });
        $task->hashes->each(function($value){
            $value->delete();
        });
        $task->activities->each(function($value){
            $value->delete();
        });
        $task->tempItems()->delete();
        return $task;
    }

    public function syncUsers(Task $task, array $userIds)
    {
        return $task->users()->sync($userIds);
    }

    public function syncCategories(Task $task, array $catIds)
    {
        return $task->categories()->sync($catIds);
    }

    public function saveText(Task $task, $body)
    {
        $body = !empty($body) ? $body : '';
        if (!empty($task->text) && $task->text->body == $body) {
            return $task;
        }
        $data = compact('body');
        if($task->text) {
            $task->text()->update($data);
            $task->text->searchable();
        } else {
            $task->text()->create($data);
        }
        $task->touch();
        return $task;
    }

    public function addHistory(Task $task)
    {
        $data = [
            'body' => $task->text->body,
            'author_id' => $task->author_id,
        ];
        $task->histories()->create($data);
    }

    public function loadDetailInfo(Task $task, Team $team, User $user)
    {
        $task->load(['text', 'subscribers' => function($q) use($team){
            $q->withUsername($team);
        }, 'comments' => function($q2) use($team){
            $q2->with('children');
            $q2->orderBy('created_at');
        }, 'userOptions' => function($q) use($user) {
            $q->where('user_id', $user->id);
        }, 'tempItem' => function($q) use($user) {
            $q->where('user_id', $user->id);
        },
         'categories', 'users', 'activities', 'lastActivity']);
        return $task;
    }

    public function saveTaskUserOptions(Task $task, User $user, array $pivotData)
    {
        $task->userOptions()->syncWithoutDetaching([$user->id => $pivotData]);
    }

    public function loadHistoryInfo(Task $task, Team $team)
    {
        $task->load(['text', 'histories' => function($q){
            $q->orderBy('created_at', 'desc');
        }]);
        return $task;
    }

    public function setHistory(Task $task, History $history)
    {
        $task->text->update(['body' => $history->body]);
        $task->update(['author_id' => $history->author_id]);
        return $task;
    }

    public function subscribeUser(Task $task, array $userIds, $type = null)
    {
        $data = [];
        foreach ($userIds as $id) {
            $data[$id] = ['type' => $type];
        }
        return $task->subscribers()->syncWithoutDetaching($data);
    }

    public function addUnsubscribeHashBySyncUsers(Task $task, array $sync) : void
    {
        if(count($sync['attached'])) {
            foreach($sync['attached'] as $userId) {
                $this->addUnsubscribeHashByUserId($task, $userId);
            }
        }
    }

    public function addUnsubscribeHashByUserId(Task $task, int $userId) : void
    {
        $hash = $task->hashes()->whereHas('users', function($q) use($userId) {
            $q->where('hashable_id', $userId);
        })->firstOrNew(['type' => Hash::TYPE_TASK_SUBSCRIBE]);

        if( ! $hash->exists) {
            $hash->hash = randomMD5();
            $hash->save();
            $hash->users()->attach($userId);
            $hash->tasks()->attach($task->id);
        }
    }

    public function unsubscribeUser(Task $task, array $userIds)
    {
        $task->subscribers()->detach($userIds);
        return $task;
    }

    public function loadMailInfo(Task $task, Team $team, User $user)
    {
        return $task->load(['author'=>function($q) use($team){
                $q->withUsername($team);
            }, 'subscribers' => function($q) use($user) {
                $q->where('user_id', $user->id);
            }, 'hashes' => function($q) use($task, $user) {
                $q->taskUserSubscribed($task, $user);
            }]);
    }

    public function getSubcribedHash(string $stringHash)
    {
        $hash = Hash::where('hash', $stringHash)->where('type', Hash::TYPE_TASK_SUBSCRIBE)->has('users')->has('tasks')->with('tasks', 'users')->first();
        return $hash;
    }

    public function deleteSubscribeHash(Task $task, User $user)
    {
        $hash = Hash::taskUserSubscribed($task, $user)->first();
        $hash->delete();
    }

    public function saveTempUserItem(Task $task, User $user, array $data)
    {
        $item = $task->tempItems()->withTrashed()->firstOrNew(['user_id' => $user->id]);
        $item->fill($data)->save();
        $item->trashed() ? $item->restore() : null;
        return $item;
    }

    public function deleteTempUserItem(Task $task, User $user):void
    {
        $task->tempItems()->where('user_id', $user->id)->delete();
    }

    public function forceDeleteTempUserItem(Task $task, User $user):void
    {
        $task->tempItems()->where('user_id', $user->id)->forceDelete();
    }

    public function restoreTempUserItem(Task $task, User $user):void
    {
        $task->tempItems()->where('user_id', $user->id)->restore();
    }

    public function searchTasks(Team $team, User $user, Request $request)
    {
        $query = $team->tasks();

        //for categories
        $categories = $request->get('categories', null);
        $categoriesIds = $categories ? array_values($categories) : null;
        $callbackOrCats = function($q) use($categoriesIds){
            $q->whereIn('id', $categoriesIds);
        };
        $callbackAndCats = function($q) use($categoriesIds){
            foreach($categoriesIds as $id) {
                $q->whereHas('categories', function($q2) use ($id){
                    $q2->where('category_id', $id);
                });
            };
        };
        $callbackInvertAndCats = function($q) use($categoriesIds){
            foreach($categoriesIds as $id) {
                $q->whereDoesntHave('categories', function($q2) use ($id){
                    $q2->where('category_id', $id);
                });
            };
        };
        //for users
        $users = $request->get('users', null);
        $usersIds = $users ? array_values($users) : null;
        $callbackOrUsers = function($q) use($usersIds){
            $q->whereIn('id', $usersIds);
        };
        $callbackAndUsers = function($q) use($usersIds){
            foreach($usersIds as $id) {
                $q->whereHas('users', function($q2) use ($id){
                    $q2->where('user_id', $id);
                });
            };
        };
        $callbackInvertAndUsers = function($q) use($usersIds){
            foreach($usersIds as $id) {
                $q->whereDoesntHave('users', function($q2) use ($id){
                    $q2->where('user_id', $id);
                });
            };
        };
        //for authors
        $authors = $request->get('authors', null);
        $authorsIds = $authors ? array_values($authors) : null;
        $callbackOrAuthors = function($q) use($authorsIds){
            $q->whereIn('author_id', $authorsIds);
        };
        $callbackInvertOrAuthors = function($q) use($authorsIds){
            $q->whereNotIn('author_id', $authorsIds);
        };

        $callbackInvertAndAuthors = function($q) use($authorsIds){
            foreach($authorsIds as $id) {
                $q->whereDoesntHave('users', function($q2) use ($id){
                    $q2->where('user_id', $id);
                });
            };
        };
        //is_unassigned
        $isUnassigned = $request->get('is_unassigned', null);
        $callbackIsUnassigned = function($q) {
            $q->whereDoesntHave('users');
            $q->public();
        };
        $callbackInvertIsUnassigned = function($q) {
            $q->has('users');
        };
        //is_draft
        $isDraft = $request->get('is_draft', null);
        $callbackIsDraft = function($q) {
            $q->whereNotNull('drafted_by');
        };
        $callbackInvertIsDraft = function($q) {
            $q->whereNull('drafted_by');
        };

        //is_unsorted
        $isUnsorted = $request->get('is_unsorted', null);
        $callbackIsUnsorted = function($q) {
            $q->doesntHave('categories');
        };
        $callbackInvertIsUnsorted = function($q) {
            $q->has('categories');
        };

        //due date filters
        $dueDateType = $request->get('due_date_type', null);
        if($request->due_date_from) {
            $dueDateType = 'period';
        }

        switch ($dueDateType) {
            case 'no':
                $callbackDueDateType = function($q) {
                    $q->whereNull('due_date');
                };
                $callbackInvertDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                };
                break;
            case 'overdue':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '<=', now());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>', now());
                };
                break;
            case 'today':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', now()->startOfDay())->where('due_date', '<=', now()->endOfDay());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->where(function($q2){
                        $q2->where('due_date', '<', now()->startOfDay());
                        $q2->orWhere('due_date', '>', now()->endOfDay());
                    });
                };
                break;
            case 'tommorow':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', now()->addDay(1)->startOfDay())->where('due_date', '<=', now()->addDay(1)->endOfDay());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->where(function($q2){
                        $q2->where('due_date', '<', now()->addDay(1)->startOfDay());
                        $q2->orWhere('due_date', '>', now()->addDay(1)->endOfDay());
                    });
                };
                break;
            case 'thisWeek':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', now()->startOfWeek()->startOfDay())->where('due_date', '<=', now()->endOfWeek()->endOfDay());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->where(function($q2){
                        $q2->where('due_date', '<', now()->startOfWeek()->startOfDay());
                        $q2->orWhere('due_date', '>', now()->endOfWeek()->endOfDay());
                    });
                };
                break;
            case 'nextWeek':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', now()->addWeek(1)->startOfWeek()->startOfDay())->where('due_date', '<=', now()->addWeek(1)->endOfWeek()->endOfDay());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->where(function($q2){
                        $q2->where('due_date', '<', now()->addWeek(1)->startOfWeek()->startOfDay());
                        $q2->orWhere('due_date', '>', now()->addWeek(1)->endOfWeek()->endOfDay());
                    });
                };
                break;
            case 'thisMonth':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', now()->startOfMonth()->startOfDay())->where('due_date', '<=', now()->endOfMonth()->endOfDay());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->where(function($q2){
                        $q2->where('due_date', '<', now()->startOfMonth()->startOfDay());
                        $q2->orWhere('due_date', '>', now()->endOfMonth()->endOfDay());
                    });
                };
                break;
            case 'nextMonth':
                $callbackDueDateType = function($q) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', now()->addMonth(1)->startOfMonth()->startOfDay())->where('due_date', '<=', now()->addMonth(1)->endOfMonth()->endOfDay());
                };
                $callbackInvertDueDateType = function($q) {
                    $q->where(function($q2){
                        $q2->where('due_date', '<', now()->addMonth(1)->startOfMonth()->startOfDay());
                        $q2->orWhere('due_date', '>', now()->addMonth(1)->endOfMonth()->endOfDay());
                    });
                };
                break;
            case 'period':
                $dueDateFrom = Carbon::parse($request->due_date_from);
                $dueDateTo = Carbon::parse($request->due_date_to);
                $callbackDueDateType = function($q) use($dueDateFrom, $dueDateTo) {
                    $q->whereNotNull('due_date');
                    $q->where('due_date', '>=', $dueDateFrom)->where('due_date', '<=', $dueDateTo);
                };
                $callbackInvertDueDateType = function($q) use($dueDateFrom, $dueDateTo) {
                    $q->where(function($q2) use($dueDateFrom, $dueDateTo){
                        $q2->where('due_date', '<', $dueDateFrom);
                        $q2->orWhere('due_date', '>', $dueDateTo);;
                    });
                };
            break;
            default:
                $callbackDueDateType = function($q) {

                };
                $callbackInvertDueDateType = function($q) {

                };
                break;
        }

        $combine = $request->combine;

        if ( ! $request->invert) {
            if ($combine == 'and') {
                if ($categoriesIds) {
                    $query->where($callbackAndCats);
                }
                if ($usersIds) {
                    $query->where($callbackAndUsers);
                }
                if ($authorsIds) {
                    $query->whereHas('author', $callbackOrAuthors);
                }
                if($dueDateType) {
                    $query->where($callbackDueDateType);
                }
                if($isDraft) {
                    $query->where($callbackIsDraft);
                }
                if($isUnassigned) {
                    $query->where($callbackIsUnassigned);
                }
                if($isUnsorted) {
                    $query->where($callbackIsUnsorted);
                }
            } elseif ($combine == 'or') {
                $query->where(function($q) use($categoriesIds, $callbackOrCats, $usersIds, $callbackOrUsers, $authorsIds, $callbackOrAuthors, $dueDateType, $callbackDueDateType, $isDraft, $callbackIsDraft, $isUnassigned, $callbackIsUnassigned, $isUnsorted, $callbackIsUnsorted){
                    if ($categoriesIds) {
                        $q->orWhereHas('categories', $callbackOrCats);
                    }
                    if ($usersIds) {
                        $q->orWhereHas('users', $callbackOrUsers);
                    }
                    if ($authorsIds) {
                       $q->orWhere($callbackOrAuthors);
                    }
                    if($dueDateType) {
                        $q->where($callbackDueDateType);
                    }
                    if($isDraft) {
                        $q->orWhere($callbackIsDraft);
                    }
                    if($isUnassigned) {
                        $q->orWhere($callbackIsUnassigned);
                    }
                    if($isUnsorted) {
                        $q->orWhere($callbackIsUnsorted);
                    }
                });
            }
        } else {
            //INVERS
            if ($combine == 'and') {
                if ($categoriesIds) {
                    $query->where($callbackInvertAndCats);
                }
                if ($usersIds) {
                    $query->where($callbackInvertAndUsers);
                }
                if ($authorsIds) {
                    $query->where($callbackInvertOrAuthors);
                }
                if($dueDateType) {
                    $query->where($callbackInvertDueDateType);
                }
                if($isDraft) {
                    $query->where($callbackInvertIsDraft);
                }
                if($isUnassigned) {
                    $query->where($callbackInvertIsUnassigned);
                }
                if($isUnsorted) {
                    $query->where($callbackInvertIsUnsorted);
                }
            } elseif ($combine == 'or') {
                $query->where(function($q) use($categoriesIds, $callbackOrCats, $usersIds, $callbackOrUsers, $authorsIds, $callbackInvertOrAuthors, $dueDateType, $callbackInvertDueDateType, $isDraft, $callbackInvertIsDraft, $isUnassigned, $callbackInvertIsUnassigned, $isUnsorted, $callbackInvertIsUnsorted){
                    if ($categoriesIds) {
                        $q->orWhereDoesntHave('categories', $callbackOrCats);
                    }
                    if ($usersIds) {
                        $q->orWhereDoesntHave('users', $callbackOrUsers);
                    }
                    if ($authorsIds) {
                        $q->orWhere($callbackInvertOrAuthors);
                    }
                    if($dueDateType) {
                        $q->orWhere($callbackInvertDueDateType);
                    }
                    if($isDraft) {
                        $q->orWhere($callbackInvertIsDraft);
                    }
                    if($isUnassigned) {
                        $q->orWhere($callbackInvertIsUnassigned);
                    }
                    if($isUnsorted) {
                        $q->orWhere($callbackInvertIsUnsorted);
                    }
                });
            }
        }

        //type (private or public)
        $type = $request->get('type', null);
        switch ($type) {
            case 'private':
                $query->private();
                break;
            case 'public':
                $query->public();
                break;
        }

        return $query->forList($team, $user)->onlyUserPrivate($user)->get();
    }

    public function forceDeleteWithRelations(Task $task)
    {
        $task->users()->detach();
        $task->categories()->detach();
        $task->text()->delete();
        $task->histories()->delete();
        $task->subscribers()->detach();
        foreach ($task->userNotifications as $notify) {
            $notify->delete();
        }
        $task->comments->each(function($value){
            $value->delete();
        });
        $task->comments()->detach();
        foreach($task->hashes as $hash) {
            $hash->delete();
        }
        $task->hashes()->detach();
        $task->activities()->delete();
        $task->forceDelete();
    }
}
