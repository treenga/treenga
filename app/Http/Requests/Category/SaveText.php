<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\ApiRequest;

class SaveText extends ApiRequest
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
            'body' => 'required',
            'subscribers' => 'nullable|array|min:1',
        ];
    }
}
