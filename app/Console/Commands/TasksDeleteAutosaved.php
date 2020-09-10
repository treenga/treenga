<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FakerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\TempTask;
use App\Mail\CronEmail;

class TasksDeleteAutosaved extends Command
{
    protected $fakerService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:deleteautosaved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete autosaved tasks';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        TempTask::onlyTrashed()->whereDate('deleted_at', '<=', now()->subDay())->forceDelete();
        Mail::to("mail@treenga.com")->send(new CronEmail('Delete Autosaved Tasks'));
    }
}
