<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitialSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status');
            $table->string('name');
            $table->string('email')->unique();
            $table->tinyInteger('role')->unsigned()->default(0);
            $table->boolean('is_team_author')->default(true);
            $table->string('new_email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->integer('private_task_sequence')->default(0);
            $table->json('private_treestate')->nullable();
            $table->json('private_current_state')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
        });
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('hashes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hash');
            $table->string('type')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
        Schema::create('hashables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hash_id');
            $table->integer('hashable_id');
            $table->string('hashable_type');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64);
            $table->string('slug')->nullable(true);
            $table->integer('task_sequence')->default(0);
            $table->boolean('private')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('team_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('team_id');
            $table->string('username', 64)->nullable();
            $table->boolean('is_owner')->default(false);
            $table->boolean('current')->default(false);
            $table->json('filter')->nullable();
            $table->json('treestate')->nullable();
            $table->json('current_state')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type');
            $table->string('name');
            $table->nestedSet();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('categoryables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->integer('categoryable_id');
            $table->string('categoryable_type');
            $table->timestamps();
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('team_id')->nullable();
            $table->string('path');
            $table->string('size');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::statement("ALTER TABLE files ALTER COLUMN size TYPE integer USING (size::integer);");
        Schema::create('histories', function (Blueprint $table) {
            $table->increments('id');
            $table->text('body');
            $table->integer('historyable_id');
            $table->string('historyable_type');
            $table->integer('author_id');
            $table->timestamps();
        });
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
            $table->integer('author_id');
            $table->integer('owner_id')->nullable();
            $table->string('name');
            $table->integer('drafted_by')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->tinyInteger('type');
            $table->integer('team_task_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('task_user', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('task_id');
            $table->timestamps();
        });
        Schema::create('category_task', function (Blueprint $table) {
            $table->integer('task_id');
            $table->integer('category_id');
            $table->timestamps();
        });
        DB::statement('CREATE INDEX category_task_category_id on category_task (category_id);');
        DB::statement('CREATE INDEX category_task_task_id on category_task (task_id);');
        Schema::create('category_texts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('task_texts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_id');
            $table->text('body');
            $table->timestamps();
        });
        try {
            DB::statement("ALTER TABLE task_texts ADD searchable tsvector null;");
            DB::statement("CREATE INDEX texts_searchable_index ON task_texts USING GIN (searchable);");
        } catch (\Throwable $th) {}
        Schema::create('task_subscriber', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('task_id');
            $table->integer('type')->nullable();
            $table->timestamps();
        });
        Schema::create('notifyables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->uuid('notification_id');
            $table->integer('notifyables_id');
            $table->string('notifyables_type');
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('author_id');
            $table->text('body');
            $table->string('username')->nullable();
            $table->nestedSet();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('comentables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('comment_id');
            $table->integer('comentables_id');
            $table->string('comentables_type');
            $table->timestamps();
        });
        Schema::create('category_subscriber', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('category_id');
            $table->timestamps();
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('author_id')->nullable();
            $table->string('text')->nullable();
            $table->integer('activityable_id');
            $table->string('activityable_type');
            $table->string('username')->nullable();
            $table->timestamps();
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        Schema::create('user_read_comment', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('comment_id');
            $table->timestamps();
        });
        Schema::create('user_task_option', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('task_id');
            $table->json('commentsstate')->nullable();
            $table->timestamps();
        });
        Schema::create('temp_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_id');
            $table->integer('user_id');
            $table->string('name')->nullable();
            $table->text('body')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('user_unread_activity', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('activity_id');
            $table->timestamps();
        });
        Schema::create('user_task_viewed', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('task_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('category_subscriber');
        Schema::dropIfExists('category_task');
        Schema::dropIfExists('category_texts');
        Schema::dropIfExists('categoryables');
        Schema::dropIfExists('comentables');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('files');
        Schema::dropIfExists('hashables');
        Schema::dropIfExists('hashes');
        Schema::dropIfExists('histories');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notifyables');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('task_subscriber');
        Schema::dropIfExists('task_texts');
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('temp_tasks');
        Schema::dropIfExists('user_read_comment');
        Schema::dropIfExists('user_task_option');
        Schema::dropIfExists('user_task_viewed');
        Schema::dropIfExists('user_unread_activity');
        Schema::dropIfExists('users');
    }
}
