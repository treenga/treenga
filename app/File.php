<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'team_id',
        'path',
        'size',
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleted(function ($model)
        {
            if($model->forceDeleting){
                Storage::delete($model->path);
                $arr = explode('/', $model->path);
                array_pop($arr);
                $directory = implode('/', $arr);
                if(empty(Storage::files($directory))) {
                    Storage::deleteDirectory($directory);
                }
            }
        });
    }
    /**Start Relations */
    /**End Relations */

    /**Start Scopes*/
    /**End Scopes */

    /**Start Mutators*/
    /**End Mutators */

    /**Start Helper*/
    /**End Helper*/
}
