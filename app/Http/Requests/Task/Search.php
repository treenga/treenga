<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\ApiRequest;

class Search extends ApiRequest
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
            'combine' => 'required|in:or,and',
            'invert' => 'required|boolean',
            'categories' => 'nullable|array|min:1',
            'users' => 'nullable|array|min:1',
            'authors' => 'nullable|array|min:1',
            'due_date_type' => 'nullable|in:no,overdue,today,tommorow,thisWeek,nextWeek,thisMonth,nextMonth',
            'due_date_from' => 'nullable|date',
            'due_date_to' => 'nullable|date|required_with:due_date_from,',
            'is_draft' => 'nullable|boolean',
            'is_unassigned' => 'nullable|boolean',
            'is_unsorted' => 'nullable|boolean',
        ];
    }
}
