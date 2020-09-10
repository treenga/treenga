<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\ApiRequest;
use App\Task;

class MassSetAttributes extends ApiRequest
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
        $actions = (new Task())->getMassActions();
        return [
            'action' => 'required|in:,'.$actions->implode(','),
            'tasks' => 'required|array|min:1',
            'categories' => 'nullable|array',
            'users' => 'nullable|array',
            'due_date' => 'required_if:action,'.Task::MASS_ACTION_SET_DUE_DATE.'|date',
        ];
    }
}
