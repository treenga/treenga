<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use App\User;
use App\Team;
use App\Category;
use App\Task;
use App\TaskText;

class FakerService extends CoreService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function runSeed(array $emails):void
    {
        $users = collect([]);
        foreach($emails as $email) {
            $user = $this->userRepository->getItemByEmail($email);
            if($user) {
                $users->push($user);
            } else {
                $user = factory(User::class)->create(['email' => $email]);
                $users->push($user);
            }
        }
        foreach($users as $user) {
            //teams
            for ($k=0; $k < 3; $k++) {
                $team = factory(Team::class)->create();
                $user->teams()->attach([$team->id => ['is_owner' => true, 'username' => 'owner']]);
                //categories
                $rootCategories = factory(Category::class, 33)->make(['type' => Category::TYPE_PUBLIC])->each(function($item){
                    $item->makeRoot()->save();
                });
                $depth1Categories = collect([]);
                $depth2Categories = collect([]);
                for ($i=0; $i < 33; $i++) {
                    $childCategory = factory(Category::class)->create(['type' => Category::TYPE_PUBLIC, 'parent_id' => $rootCategories->random()]);
                    $depth1Categories->push($childCategory);
                }
                for ($i=0; $i < 34; $i++) {
                    $childCategory = factory(Category::class)->create(['type' => Category::TYPE_PUBLIC, 'parent_id' => $depth1Categories->random()]);
                    $depth2Categories->push($childCategory);
                }
                $categories = $rootCategories->merge($depth1Categories)->merge($depth2Categories);
                $team->categories()->attach($categories->pluck('id'));

                $tasks = collect();
                for ($i=0; $i < 2000; $i++) {
                    $dueDate = ($i % 2 == 0) ? now()->addDays(rand(1, 356)) : null;
                    $task = factory(Task::class)->create(['author_id' => $user->id, 'team_id' => $team->id, 'type' => Task::TYPE_PUBLIC, 'due_date' => $dueDate]);
                    $task->text()->save(factory(TaskText::class)->make());
                    $task->categories()->attach($categories->random(5)->pluck('id'));
                }
                for ($i=0; $i < 8000; $i++) {
                    $dueDate = ($i % 2 == 0) ? now()->addDays(rand(1, 356)) : null;
                    $task = factory(Task::class)->create(['author_id' => $user->id, 'team_id' => $team->id, 'type' => Task::TYPE_PUBLIC, 'due_date' => $dueDate, 'deleted_at' => now()]);
                    $task->text()->save(factory(TaskText::class)->make());
                    $task->categories()->attach($categories->random(5)->pluck('id'));
                }
            }
        }
    }


}