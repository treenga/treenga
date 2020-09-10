<?php

use Illuminate\Support\Facades\Broadcast;


/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('team.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('task.{id}', function ($user, $id) {
    return true;
});
