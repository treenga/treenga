<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'name',
        'body',
    ];
    /**Start Relations */
    /**End Relations */

    /**Start Scopes*/
    /**End Scopes */

    /**Start Mutators*/
    /**End Mutators */

    /**Start Helper*/
    /**End Helper*/
}
