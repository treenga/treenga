<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\ApiRequest;

class Update extends ApiRequest
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
            'name' => 'required',
            'body' => 'nullable',
            'categories' => 'nullable|array',
            'users' => 'nullable|array|min:1',
            'subscribers' => 'nullable|array|min:1',
            'due_date' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'categories.required' => 'Select at least one category',
        ];
    }
}
