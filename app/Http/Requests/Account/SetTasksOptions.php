<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class SetTasksOptions extends ApiRequest
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
            'show_task_all_comments' => 'required|boolean',
            'show_task_details' => 'required|boolean',
        ];
    }
}
