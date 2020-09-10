<?php

use Faker\Generator as Faker;
use App\User;
use App\Team;
use App\Task;

$factory->define(App\Task::class, function (Faker $faker, $attr) {
    return [
        'team_id' => array_get($attr, 'team_id', optional(Team::inRandomOrder()->first())->id),
        'author_id' => array_get($attr, 'author_id', optional(User::inRandomOrder()->first())->id),
        'name' => $faker->sentence(4),
        'drafted_by' => null,
        'due_date' => $faker->dateTimeBetween($startDate = '+1 day', $endDate = '+1 years'),
        'type' => array_get($attr, 'type', (new Task())->getTypes()->keys()->random()),
    ];
});
