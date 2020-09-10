<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\ApiRequest;

class GetDrafts extends ApiRequest
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
            'withTrashed' => 'nullable|boolean',
            'onlyTrashed' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
        ];
    }
}
