<?php

use Faker\Generator as Faker;

$factory->define(App\TaskText::class, function (Faker $faker) {
    return [
        'body' => $faker->realText(rand(500, 2000)),
    ];
});
