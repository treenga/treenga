<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('v1')->group(function() {
    // Public routes
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::get('verify/{hash}', 'AuthController@verify');
    Route::post('recovery', 'AuthController@recovery');
    Route::post('reset', 'AuthController@reset');
    //Invite
    Route::prefix('invite')->group(function(){
        Route::get('{stringhash}', 'AuthController@getInfoByInviteHash');
        Route::post('{stringhash}', 'AuthController@setPasswordForInviteUser');
    });
    //Task public routes
    Route::prefix('tasks')->group(function(){
        Route::get('unsubscribe/{stringhash}', 'TaskController@publicUnsubscribe');
    });
    Route::middleware('auth:api')->group(function(){
        //Upload (private) routes
        Route::post('upload', 'AuthController@upload');
        Route::get('logout', 'AuthController@logout');
        //Account routes
        Route::prefix('account')->group(function(){
            Route::get('alert/{type}', 'AccountController@closeAlert');
            Route::get('/', 'AccountController@account');
            Route::post('changeEmail', 'AccountController@changeEmail');
            Route::post('changePassword', 'AccountController@changePassword');
            Route::put('tasksoptions', 'AccountController@setTasksOptions');
            Route::post('delete', 'AccountController@deleteAccount');
        });
        //Teams routes
        Route::middleware('update')->prefix('teams')->group(function(){
            Route::get('/', 'TeamController@list');
            Route::get('{team}', 'TeamController@getTeam');
            Route::post('/', 'TeamController@create');
            Route::put('{team}', 'TeamController@update');
            Route::delete('{team}', 'TeamController@deleteTeam');
            Route::post('{team}/filter', 'TeamController@setFilter');
            Route::put('{team}/treestate', 'TeamController@setTreestate');
            Route::put('{team}/lasttask', 'TeamController@setLastTask');
            Route::get('{team}/autocomplite', 'TeamController@autocomplite');
            Route::put('{team}/name', 'TeamController@changeName');
            Route::post('{team}/user', 'TeamController@addUser');
            Route::delete('{team}/user/{user}', 'TeamController@deleteUser');
            Route::get('{team}/info', 'TeamController@info');
            Route::get('{team}/shortinfo', 'TeamController@shortInfo');
            //Team categories routes
            Route::prefix('{team}/cats')->group(function(){
                Route::post('/', 'CategoryController@addPublicCategory');
                Route::get('{category}', 'CategoryController@getItem');
                Route::get('{category}/tasks', 'CategoryController@getTasks');
                Route::get('{category}/history', 'CategoryController@getItemHistory');
                Route::put('{category}/history/{history}', 'CategoryController@setItemHistory');
                Route::put('{category}/move', 'CategoryController@move');
                Route::post('{category}/desc', 'CategoryController@addDesc');
                Route::post('{category}/comments', 'CategoryController@addComment');
                Route::put('{category}/desc', 'CategoryController@updateDesc');
                Route::put('{category}/name', 'CategoryController@updateName');
                Route::delete('{category}', 'CategoryController@deleteItem');
                Route::get('{category}/subscribe', 'CategoryController@subscribe');
                Route::get('{category}/unsubscribe', 'CategoryController@unsubscribe');
            });
            //Team tasks routes
            Route::prefix('{team}/tasks')->group(function(){
                Route::get('/', 'TeamController@getPublicTasks');
                Route::post('/', 'TaskController@createPublicTask');
                Route::get('lastviewed', 'TeamController@getLastViewed');
                Route::get('notifications', 'TeamController@getNotifications');
                Route::get('drafts', 'TeamController@getDrafts');
                Route::get('unsorted', 'TeamController@getUnsortedTasks');
                Route::get('assigned', 'TeamController@getAssignedTask');
                Route::get('unassigned', 'TeamController@getUnassignedTask');
                Route::get('byuser/{user}', 'TeamController@getTasksByUser');
                Route::get('byauthor/{user}', 'TeamController@getTasksByAuthor');
                Route::post('draft', 'TaskController@createDraft');
                Route::post('mass', 'TaskController@setMass');
                Route::put('{task}', 'TaskController@updateTask');
                Route::post('{task}/autosave', 'TaskController@autosaveTask');
                Route::delete('{task}/autosave', 'TaskController@deleteAutosaveTask');
                Route::get('{task}/autosave/restore', 'TaskController@restoreAutosaveTask');
                Route::get('{team_task_id}', 'TaskController@getItem');
                Route::put('{task}/commentsstate', 'TaskController@saveCommentsstate');
                Route::delete('{task}', 'TaskController@deleteItem');
                Route::get('{task}/restore', 'TaskController@restoreItem');
                Route::delete('{task}/force', 'TaskController@deleteDraftForce');
                Route::get('{task}/attributes', 'TaskController@getAttributes');
                Route::put('{task}/attributes', 'TaskController@setAttributes');
                Route::get('{task}/history', 'TaskController@getItemHistory');
                Route::put('{task}/history/{history}', 'TaskController@setItemHistory');
                Route::post('{task}/comments', 'TaskController@createComment');
                Route::get('{task}/subscribe', 'TaskController@subscribe');
                Route::get('{task}/unsubscribe', 'TaskController@unsubscribe');
                Route::post('search', 'TaskController@search');
            });
        });
    });

    // Admin
    Route::prefix('admin')->namespace('Admin')->group(function() {
        Route::post('login', 'AuthController@login');
        Route::middleware('auth:api')->group(function(){
            Route::get('account', 'AuthController@account');
            Route::post('change-password', 'UsersController@changePassword');
            Route::prefix('users')->group(function(){
                Route::get('/', 'UsersController@getAll');
                Route::post('/', 'UsersController@addUser');
                Route::delete('/', 'UsersController@deleteUser');
                Route::get('{user}', 'UsersController@getOne');
                Route::post('{user}/founds', 'UsersController@addFounds');
                Route::post('{user}/history', 'UsersController@getHistory');
                Route::post('{user}/show_link', 'UsersController@setShowLink');
            });
        });
    });
});
