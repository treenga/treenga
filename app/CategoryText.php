<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryText extends Model
{
    protected $fillable = [
        'body',
    ];

    public function getCountWordAttribute()
    {
        $parsedText = trim(strip_tags(str_replace(['&nbsp;', '<br>'], ' ', $this->body)));
        return count(preg_split('/\s+/', $parsedText));
    }
}
