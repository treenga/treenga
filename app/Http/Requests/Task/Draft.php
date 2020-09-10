<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\ApiRequest;

class Draft extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'task_id' => 'nullable',
            'name' => 'nullable',
            'body' => 'nullable',
            'categories' => 'nullable|array|min:1',
            'users' => 'nullable|array',
            'due_date' => 'nullable|date',
            'subscribers' => 'nullable|array|min:1',
        ];
    }
}
